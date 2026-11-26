<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Models\User;
use App\Modules\Orders\Models\Driver;
use App\Modules\Organization\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Small driver admin: drivers are linked to one branch (location_id). */
class DriverController
{
    public function index(Request $request): View
    {
        $locationId = $request->get('location_id');
        $drivers = Driver::with(['location', 'user'])
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->orderBy('location_id')->orderBy('name')->paginate(20);
        $locations = Location::whereIn('type', ['dark', 'shop'])->where('active', true)->orderBy('name')->get();

        return view('drivers.index', compact('drivers', 'locations', 'locationId'));
    }

    public function create(): View
    {
        $locations = Location::whereIn('type', ['dark', 'shop'])->where('active', true)->orderBy('name')->get();
        $users = User::where('active', true)->orderBy('name')->get(['id', 'name', 'phone']);

        return view('drivers.create', compact('locations', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'location_id' => ['required', 'exists:locations,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'active' => ['nullable', 'boolean'],
        ]);

        $location = Location::find($data['location_id']);
        if (! $location || ! in_array($location->type, ['dark', 'shop'], true) || ! $location->active) {
            return back()->withErrors(['location_id' => 'الفرع غير متاح'])->withInput();
        }

        Driver::create([
            'name' => trim($data['name']),
            'phone' => $data['phone'] ?? null,
            'location_id' => $data['location_id'],
            'user_id' => $data['user_id'] ?? null,
            'active' => (bool) ($data['active'] ?? true),
        ]);

        return redirect()->route('drivers.index', ['location_id' => $data['location_id']])
            ->with('status', 'تم إضافة الطيار');
    }
}
