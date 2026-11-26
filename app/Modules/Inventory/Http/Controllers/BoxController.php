<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BoxController
{
    public function __construct(private readonly StockService $stock)
    {
    }

    public function index(): View
    {
        $sn = filter_var(request('sn'), FILTER_VALIDATE_INT) ?: null;
        $box = $sn ? Box::with(['item', 'location'])->find($sn) : null;
        $history = $box ? $this->stock->boxHistory($box->sn) : collect();

        return view('boxes.index', compact('box', 'history', 'sn'));
    }

    /** ستيكر العلبة للطباعة: الرقم المميز والصنف والوزن وتاريخ الإنتاج والصلاحية. */
    public function sticker(Box $box): View
    {
        $box->load(['item', 'location']);

        return view('boxes.sticker', compact('box'));
    }

    public function resolveMissing(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sn' => ['required', 'integer'],
            'found' => ['required', 'boolean'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $msg = $this->stock->resolveMissing((int) $data['sn'], (bool) $data['found'], $data['note'] ?? null, $request->user()?->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['box' => $e->getMessage()]);
        }

        return back()->with('status', $msg);
    }
}