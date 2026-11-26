<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Modules\Catalog\Models\Item;
use App\Modules\Notifications\Services\NotificationService;
use App\Modules\Orders\Models\Driver;
use App\Modules\Orders\Models\OnlineOrder;
use App\Modules\Orders\Services\OrderService;
use App\Modules\Organization\Models\Location;
use App\Modules\Sales\Models\ShiftCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly NotificationService $notifications,
    ) {
    }

    public function index(): View
    {
        $status = request('status');
        $orders = OnlineOrder::with('customer')->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')->paginate(20);

        return view('orders.index', compact('orders'));
    }

    public function show(OnlineOrder $order): View
    {
        $order->load(['lines.item', 'boxes', 'customer', 'driverRecord']);
        $drivers = Driver::forLocation($order->location_id)->get();
        $collections = ShiftCollection::with('user')->where('order_id', $order->id)->orderByDesc('id')->get();

        return view('orders.show', compact('order', 'drivers', 'collections'));
    }

    /** Customer-service order creation form. */
    public function create(): View
    {
        $branches = Location::whereIn('type', ['dark', 'shop'])->where('active', true)->orderBy('sort')->get();
        $items = Item::where('active', true)->orderBy('name')->get(['id', 'name', 'unit']);

        return view('orders.create', compact('branches', 'items'));
    }

    /** Customer-service order creation. Branch is auto-picked from the area when not chosen. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:120'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'pay_method' => ['nullable', 'in:cash,card,instapay'],
            'note' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1', 'max:40'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.qty' => ['required', 'numeric', 'min:0', 'max:50'],
        ]);

        $lines = collect($data['lines'])
            ->filter(fn ($l) => (float) $l['qty'] > 0)
            ->map(fn ($l) => ['item_id' => (int) $l['item_id'], 'qty' => (float) $l['qty']])
            ->values()->all();

        if ($lines === []) {
            return back()->withErrors(['lines' => 'السلة فاضية'])->withInput();
        }

        try {
            $order = $this->orders->createStaffOrder([
                'phone' => $data['phone'],
                'name' => $data['name'],
                'address' => $data['address'],
                'area' => $data['area'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'delivery_fee' => $data['delivery_fee'] ?? null,
                'pay_method' => $data['pay_method'] ?? 'cash',
                'note' => $data['note'] ?? null,
                'lines' => $lines,
            ], $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['order' => $e->getMessage()])->withInput();
        }

        return redirect()->route('orders.show', $order)->with('status', 'تم إنشاء الطلب #'.$order->no);
    }

    public function status(Request $request, OnlineOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:accepted,preparing,ready,out,delivered,cancelled'],
            'driver' => ['nullable', 'string', 'max:120'],
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['status'] === 'cancelled') {
            abort_unless($request->user()->hasPermission('orders.cancel'), 403, 'الإلغاء محتاج صلاحية');
        }

        try {
            $this->orders->setStatus(
                $order,
                $data['status'],
                $data['driver'] ?? null,
                $data['reason'] ?? null,
                $request->user()->id,
                isset($data['driver_id']) ? (int) $data['driver_id'] : null,
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        $this->notifications->orderStatus($order->fresh());

        return back()->with('status', 'تم تحديث حالة الطلب');
    }

    public function scan(Request $request, OnlineOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'qty' => ['nullable', 'numeric'],
        ]);

        try {
            $name = $this->orders->scan($order, $data['code'], isset($data['qty']) ? (float) $data['qty'] : null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['scan' => $e->getMessage()]);
        }

        return back()->with('status', 'تم تحضير: '.$name);
    }

    public function unscan(Request $request, OnlineOrder $order): RedirectResponse
    {
        $data = $request->validate(['box_sn' => ['required', 'integer']]);

        try {
            $this->orders->unscan($order, (int) $data['box_sn']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['scan' => $e->getMessage()]);
        }

        return back()->with('status', 'تم التراجع');
    }

    /**
     * Receiving / collection (الشاشة الرابعة): the order must be 'out'.
     * Requires the collection method and the exact total amount.
     * Cash goes to the receiver's custody on their open shift;
     * InstaPay/card are recorded for reports only.
     */
    public function receive(Request $request, OnlineOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'collection_method' => ['required', 'in:cash,instapay,card'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $total = $this->orders->deliver($order, $data['collection_method'], (float) $data['amount'], $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'تم التسليم والتحصيل — الإجمالي: '.$total);
    }

    /** Legacy name for the receiving endpoint; same behaviour as receive(). */
    public function deliver(Request $request, OnlineOrder $order): RedirectResponse
    {
        return $this->receive($request, $order);
    }
}
