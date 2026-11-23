<?php

namespace App\Modules\Purchasing\Http\Controllers;

use App\Modules\Purchasing\Models\OverrideRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverrideController
{
    public function index(): View
    {
        $overrides = OverrideRequest::orderByDesc('id')->paginate(20);

        return view('overrides.index', compact('overrides'));
    }

    public function decide(Request $request, OverrideRequest $override): RedirectResponse
    {
        $data = $request->validate([
            'approve' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($override->status !== 'pending') {
            return back()->withErrors(['override' => 'التجاوز ده مش مستني قرار']);
        }

        $override->update([
            'status' => $request->boolean('approve') ? 'approved' : 'rejected',
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
            'decision_note' => $data['note'] ?? null,
        ]);

        return back()->with('status', 'تم تحديث التجاوز');
    }
}