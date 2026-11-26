<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Modules\Organization\Models\Location;
use App\Modules\Orders\Models\Customer;
use App\Modules\Orders\Models\Driver;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\Shift;
use App\Modules\Sales\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController
{
    public function __construct(private readonly PosService $pos)
    {
    }

    public function index(): View
    {
        $openShift = Shift::with(['location:id,name', 'cashierUser:id,name'])
            ->where('cashier', auth()->id())
            ->where('status', 'open')
            ->first();

        if ($openShift) {
            $openShift->setAttribute('location_name', $openShift->location?->name);
            $openShift->setAttribute('cashier_name', $openShift->cashierUser?->name);
        }

        $user = auth()->user();
        $locQ = Location::whereIn('type', ['dark', 'shop'])->where('active', true)->orderBy('name');
        if ($user && !$user->isOwner()) {
            $ids = $user->accessibleLocationIds();
            if (!empty($ids)) $locQ->whereIn('id', $ids);
        }
        $locations = $locQ->get();
        $drivers = \App\Models\User::where('active', true)->where('is_driver', true)->orderBy('name')->get(['id','name']);
        return view('pos.index', compact('openShift', 'locations', 'drivers'));
    }

    public function open(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
        ]);

        $this->pos->openShift($data['location_id'], (float) $data['opening_cash'], auth()->id());

        return back()->with('status', 'تم فتح الوردية');
    }

    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
            'code' => ['required', 'string'],
        ]);

        try {
            return response()->json(['ok' => true, 'line' => $this->pos->lookup($data['location_id'], $data['code'])]);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function search(Request $request): JsonResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json($this->pos->search($data['location_id'], $data['q'] ?? ''));
    }

    /** Customer lookup for the POS by phone (optional customer on a direct sale). */
    public function customer(Request $request): JsonResponse
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:20']]);
        $phone = preg_replace('/\D+/', '', $data['phone']);
        $customer = Customer::where('phone', $phone)->first();

        if (! $customer) {
            return response()->json(['ok' => false]);
        }

        return response()->json(['ok' => true, 'customer' => [
            'id' => $customer->id, 'name' => $customer->name,
            'phone' => $customer->phone, 'address' => $customer->address, 'area' => $customer->area,
        ]]);
    }

    /**
     * "تجهيز" (Prepare): read-only invoice preview — trays, weights,
     * prices, discount, delivery fee and total — before payment.
     * Writes nothing; the cashier then pays via sale().
     */
    public function prepare(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shift_id' => ['required', 'exists:shifts,id'],
            'lines' => ['required', 'array'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
        ]);

        try {
            $preview = $this->pos->prepare(
                (int) $data['shift_id'],
                $data['lines'],
                (float) ($data['discount'] ?? 0),
                [
                    'delivery_fee' => (float) ($data['delivery_fee'] ?? 0),
                    'customer_id' => isset($data['customer_id']) ? (int) $data['customer_id'] : null,
                    'driver_id' => isset($data['driver_id']) ? (int) $data['driver_id'] : null,
                ],
                auth()->id(),
            );

            return response()->json(['ok' => true, 'preview' => $preview]);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function sale(Request $request): JsonResponse
    {
        $data = $request->validate([
            'shift_id' => ['required', 'exists:shifts,id'],
            'lines' => ['required', 'array'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'cash' => ['nullable', 'numeric', 'min:0'],
            'card' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'pay_method' => ['nullable', 'in:cash,card,instapay,wallet,mixed'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:32'],
            'driver_id' => ['nullable', 'exists:users,id'],
            'delivery' => ['nullable', 'boolean'],
            'idempotency_key' => ['required', 'uuid'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'driver_id' => ['nullable', 'exists:drivers,id'],
        ]);

        try {
            $sale = $this->pos->sale(
                (int) $data['shift_id'],
                $data['lines'],
                (float) ($data['discount'] ?? 0),
                (float) ($data['cash'] ?? 0),
                (float) ($data['card'] ?? 0),
                $data['idempotency_key'],
                auth()->id(),
                [
                    'delivery_fee' => (float) ($data['delivery_fee'] ?? 0),
                    'pay_method' => $data['pay_method'] ?? null,
                    'customer_name' => $data['customer_name'] ?? null,
                    'customer_phone' => $data['customer_phone'] ?? null,
                    'driver_id' => $data['driver_id'] ?? null,
                    'delivery' => (bool) ($data['delivery'] ?? false),
                    'order_source' => 'pos',
                ]
            );

            return response()->json([
                'ok' => true,
                'sale' => ['no' => $sale->no, 'total' => $sale->total, 'change' => $sale->change, 'id' => $sale->id],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function close(Request $request): Response|RedirectResponse
    {
        $data = $request->validate([
            'shift_id' => ['required', 'exists:shifts,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->pos->closeShift((int) $data['shift_id'], null, auth()->id(), $data['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['shift' => $e->getMessage()]);
        }

        $shift = Shift::with(['cashierUser', 'location', 'closedBy'])->findOrFail((int) $data['shift_id']);
        $sales = Sale::with('cashierUser')->where('shift_id', $shift->id)->orderBy('id')->get();
        $totals = app(\App\Modules\Sales\Services\ShiftService::class)->shiftTotals($shift);
        $report = view('shifts.report', compact('shift', 'sales', 'totals'))->render();

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response($report)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function sales(): View
    {
        $sales = Sale::with('cashierUser')->orderByDesc('id')->paginate(20);

        return view('sales.index', compact('sales'));
    }

    public function salesExport()
    {
        $filename = 'sales-'.now()->format('Y-m-d').'.csv';
        $sales = Sale::with('cashierUser')->orderBy('no')->get();

        return response()->streamDownload(function () use ($sales) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['رقم', 'الكاشير', 'الإجمالي', 'نقدي', 'فيزا', 'الباقي', 'التاريخ']);
            foreach ($sales as $s) {
                fputcsv($out, [$s->no, $s->cashierUser?->name, $s->total, $s->paid_cash, $s->paid_card, $s->change, $s->at]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function shifts(Request $request): View
    {
        $filters = $request->only(['from', 'to', 'location', 'cashier', 'status', 'search', 'reconcile', 'sort', 'desc', 'page', 'page_size']);
        $report = app(\App\Modules\Sales\Services\ShiftReportService::class)->report($filters);
        $locations = Location::whereIn('type', ['dark', 'shop'])->orderBy('name')->get();
        $cashiers = \App\Models\User::orderBy('name')->get(['id', 'name']);

        return view('shifts.index', compact('report', 'filters', 'locations', 'cashiers'));
    }
}
