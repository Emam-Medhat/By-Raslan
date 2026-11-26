<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Modules\Catalog\Models\Item;
use App\Modules\Orders\Models\Reservation;
use App\Modules\Orders\Services\ReservationService;
use App\Modules\Organization\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController
{
    public function __construct(private readonly ReservationService $reservations)
    {
    }

    public function index(Request $request): View
    {
        $data = $request->validate([
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:pending,converted,cancelled'],
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);

        $reservations = Reservation::with(['customer', 'location', 'convertedOrder'])
            ->when($data['date'] ?? null, fn ($q, $d) => $q->whereDate('reservation_date', $d))
            ->when($data['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($data['location_id'] ?? null, fn ($q, $l) => $q->where('location_id', $l))
            ->orderBy('reservation_date')->orderBy('id')
            ->paginate(20);

        $branches = Location::whereIn('type', ['dark', 'shop'])->where('active', true)->orderBy('sort')->get();

        return view('reservations.index', compact('reservations', 'branches'));
    }

    public function create(): View
    {
        $branches = Location::whereIn('type', ['dark', 'shop'])->where('active', true)->orderBy('sort')->get();
        $items = Item::where('active', true)->orderBy('name')->get(['id', 'name', 'unit']);

        return view('reservations.create', compact('branches', 'items'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:120'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
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
            $reservation = $this->reservations->create([
                'phone' => $data['phone'],
                'name' => $data['name'],
                'address' => $data['address'],
                'area' => $data['area'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'reservation_date' => $data['reservation_date'],
                'delivery_fee' => $data['delivery_fee'] ?? null,
                'pay_method' => $data['pay_method'] ?? 'cash',
                'note' => $data['note'] ?? null,
                'lines' => $lines,
            ], $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['order' => $e->getMessage()])->withInput();
        }

        return redirect()->route('reservations.show', $reservation)->with('status', 'تم إنشاء الحجز ليوم '.$reservation->reservation_date->toDateString());
    }

    public function show(Reservation $reservation): View
    {
        $reservation->load(['customer', 'lines.item', 'location', 'convertedOrder']);

        return view('reservations.show', compact('reservation'));
    }

    /** Manual "حوّل اوردر" — after confirming with the customer. */
    public function convert(Request $request, Reservation $reservation): RedirectResponse
    {
        try {
            $order = $this->reservations->convert($reservation, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()->route('orders.show', $order)->with('status', 'تم تحويل الحجز لأوردر #'.$order->no);
    }

    public function cancel(Reservation $reservation): RedirectResponse
    {
        try {
            $this->reservations->cancel($reservation);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'تم إلغاء الحجز');
    }

    /**
     * Factory report (تقرير المصنع): per-item aggregated quantities for
     * the chosen date (default tomorrow) + the reservations' notes.
     * The nightly scheduled job stores this for tomorrow at 00:00; the
     * page always shows the live aggregate for the chosen date.
     */
    public function factoryReport(Request $request): View
    {
        $data = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $date = $data['date'] ?? today()->addDay()->toDateString();
        $report = $this->reservations->factoryReport($date);
        $generated = $this->reservations->lastGeneratedFactoryReport($date);

        return view('reports.factory-reservations', compact('report', 'generated'));
    }
}
