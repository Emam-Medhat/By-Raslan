<?php

namespace App\Modules\Organization\Http\Controllers;

use App\Modules\Organization\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController
{
    public function index(): View
    {
        $locations = Location::with('parent')->orderBy('sort')->orderBy('name')->get();

        return view('locations.index', compact('locations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'string', 'max:64', 'unique:locations,id'],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'string', 'in:'.implode(',', array_keys(Location::TYPES))],
            'parent_id' => ['nullable', 'string', 'exists:locations,id'],
            'city' => ['nullable', 'string', 'max:120'],
        ]);

        Location::create([
            'id' => $validated['id'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'parent_id' => $validated['parent_id'] ?? null,
            'city' => $validated['city'] ?? null,
            'active' => true,
        ]);

        return redirect()->route('locations.index')->with('status', 'تمت إضافة الموقع');
    }

    public function edit(Location $location): View
    {
        $locations = Location::orderBy('name')->get();

        return view('locations.edit', compact('location', 'locations'));
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'string', 'in:'.implode(',', array_keys(Location::TYPES))],
            'parent_id' => ['nullable', 'string', 'exists:locations,id'],
            'city' => ['nullable', 'string', 'max:120'],
        ]);

        $location->update($validated);

        return redirect()->route('locations.index')->with('status', 'تم تحديث الموقع');
    }

    public function toggle(Location $location): RedirectResponse
    {
        $location->update(['active' => ! $location->active]);

        return back()->with('status', $location->active ? 'تم التفعيل' : 'تم التعطيل');
    }

    public function branch(Location $location): View
    {
        $location->load('children');
        $subs = $location->children()->where('active', true)->orderBy('sort')->get();
        return view('locations.branch', compact('location', 'subs'));
    }
}