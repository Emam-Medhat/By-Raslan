<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Modules\Organization\Models\Location;
use App\Modules\Sales\Services\DailyClosingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DailyClosingController
{
    public function __construct(private readonly DailyClosingService $closing)
    {
    }

    public function index(Request $request): View
    {
        $data = $request->validate([
            'date' => ['nullable', 'date'],
            'location_id' => ['nullable', 'exists:locations,id'],
        ]);

        $report = $this->closing->report(
            $data['date'] ?? now()->toDateString(),
            $data['location_id'] ?? null,
        );

        $locations = Location::whereIn('type', ['dark', 'shop'])->where('active', true)->orderBy('name')->get();

        return view('reports.daily-closing', compact('report', 'locations'));
    }
}
