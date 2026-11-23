<?php

namespace App\Modules\Trips\Http\Controllers;

use App\Modules\Trips\Models\Crate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrateController
{
    public function index(): View
    {
        $crates = Crate::orderBy('id')->get();

        return view('crates.index', compact('crates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['id' => ['required', 'string', 'max:32']]);
        Crate::firstOrCreate(['id' => $data['id']], ['active' => true]);

        return back()->with('status', 'تمت إضافة الصندوق');
    }

    public function toggle(Crate $crate): RedirectResponse
    {
        $crate->update(['active' => ! $crate->active]);

        return back()->with('status', $crate->active ? 'تم التفعيل' : 'تم التعطيل');
    }
}