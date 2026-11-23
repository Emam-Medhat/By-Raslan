<?php

namespace App\Modules\Trips\Http\Controllers;

use App\Modules\Trips\Models\Trip;
use App\Modules\Trips\Services\TripService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TripController
{
    public function __construct(private readonly TripService $trips)
    {
    }

    public function index(): View
    {
        $trips = Trip::withCount('boxes')->orderByDesc('id')->get();

        return view('trips.index', compact('trips'));
    }

    public function create(): View
    {
        return view('trips.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'truck' => ['required', 'string', 'max:120'],
            'driver' => ['nullable', 'string', 'max:120'],
        ]);

        $trip = $this->trips->create($data['truck'], $data['driver'] ?? null, auth()->id());

        return redirect()->route('trips.show', $trip)->with('status', 'تم إنشاء الرحلة');
    }

    public function show(Trip $trip): View
    {
        $trip->load(['crates', 'boxes']);
        $crates = \App\Modules\Trips\Models\Crate::where('active', true)->orderBy('id')->get();
        $destinations = \App\Modules\Organization\Models\Location::whereIn('type', ['dark', 'shop'])->orderBy('name')->get();

        return view('trips.show', compact('trip', 'crates', 'destinations'));
    }

    public function crateOpen(Request $request, Trip $trip): RedirectResponse
    {
        $data = $request->validate([
            'crate_id' => ['required', 'string', 'exists:crates,id'],
            'to_loc' => ['nullable', 'exists:locations,id'],
        ]);

        try {
            $to = $this->trips->openCrate($trip->id, $data['crate_id'], $data['to_loc'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['crate' => $e->getMessage()]);
        }

        return back()->with('status', $to ? "الصندوق محدد لفرع {$to}" : 'تم فتح الصندوق');
    }

    public function load(Request $request, Trip $trip): RedirectResponse
    {
        $data = $request->validate([
            'crate_id' => ['required', 'string', 'exists:crates,id'],
            'box_sn' => ['required', 'integer'],
        ]);

        try {
            $name = $this->trips->loadBox($trip->id, $data['crate_id'], (int) $data['box_sn'], auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['box' => $e->getMessage()]);
        }

        return back()->with('status', "تم تحميل العلبة — {$name}");
    }

    public function unload(Request $request, Trip $trip): RedirectResponse
    {
        $data = $request->validate(['box_sn' => ['required', 'integer']]);

        try {
            $this->trips->unloadBox($trip->id, (int) $data['box_sn']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['box' => $e->getMessage()]);
        }

        return back()->with('status', 'تم إنزال العلبة');
    }

    public function depart(Trip $trip): RedirectResponse
    {
        try {
            $this->trips->depart($trip->id, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['trip' => $e->getMessage()]);
        }

        return back()->with('status', 'تم خروج العربية');
    }
}