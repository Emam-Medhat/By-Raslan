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
    public function __construct(private readonly ReservationService $service) {}

    public function index(Request $request): View
    {
        $date = $request->string('date')->toString() ?: now()->toDateString();
        $reservations = Reservation::with(['location','lines.item','customer'])->whereDate('scheduled_for', $date)->orderByDesc('id')->get();
        $locations = Location::whereIn('type',['dark','shop'])->where('active',true)->orderBy('name')->get();
        $items = Item::where('active',true)->orderBy('name')->get(['id','name','unit']);
        $report = Reservation::with('lines.item')
            ->whereDate('scheduled_for', $request->string('report_date')->toString() ?: now()->addDay()->toDateString())
            ->whereIn('status', ['pending','confirmed','converted'])
            ->get();
        $reportRows = [];
        foreach ($report as $r) {
            foreach ($r->lines as $l) {
                $k = $l->item_id;
                if (!isset($reportRows[$k])) $reportRows[$k] = ['item' => $l->item?->name, 'qty' => 0, 'unit' => $l->item?->unit];
                $reportRows[$k]['qty'] += (float)$l->qty;
            }
        }
        $reportNotes = $report
            ->filter(fn ($reservation) => filled($reservation->notes))
            ->map(fn ($reservation) => [
                'no' => $reservation->no,
                'customer' => $reservation->customer_name,
                'note' => $reservation->notes,
            ])
            ->values();
        usort($reportRows, fn($a,$b)=>strcmp($a['item'] ?? '', $b['item'] ?? ''));
        $reportDate = $request->string('report_date')->toString() ?: now()->addDay()->toDateString();
        return view('reservations.index', compact('reservations','locations','items','date','reportRows','reportDate','reportNotes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required','exists:locations,id'],
            'customer_name' => ['nullable','string','max:120'],
            'customer_phone' => ['nullable','string','max:32'],
            'customer_address' => ['nullable','string','max:255'],
            'scheduled_for' => ['required','date'],
            'notes' => ['nullable','string','max:1000'],
            'lines' => ['required','array','min:1'],
            'lines.*.item_id' => ['required','exists:items,id'],
            'lines.*.qty' => ['required','numeric','min:0.001'],
            'lines.*.note' => ['nullable','string','max:255'],
        ]);
        try {
            $this->service->create($data, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['reservation' => $e->getMessage()])->withInput();
        }
        return back()->with('status', 'تم تسجيل الحجز.');
    }

    public function confirm(Reservation $reservation): RedirectResponse
    {
        if ($reservation->status !== 'pending') return back()->withErrors(['reservation' => 'لا يمكن تأكيد هذا الحجز.']);
        $reservation->update(['status' => 'confirmed', 'confirmed_by' => auth()->id(), 'confirmed_at' => now()]);
        return back()->with('status', 'تم تأكيد الحجز.');
    }

    public function convert(Reservation $reservation): RedirectResponse
    {
        try {
            $order = $this->service->convertToOrder($reservation, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['reservation' => $e->getMessage()]);
        }
        return redirect()->route('orders.show', $order)->with('status', 'تم تحويل الحجز إلى أوردر #' . $order->no);
    }

    public function cancel(Reservation $reservation): RedirectResponse
    {
        if ($reservation->status === 'converted') return back()->withErrors(['reservation' => 'الحجز تم تحويله بالفعل.']);
        $reservation->update(['status' => 'cancelled']);
        return back()->with('status', 'تم إلغاء الحجز.');
    }
}
