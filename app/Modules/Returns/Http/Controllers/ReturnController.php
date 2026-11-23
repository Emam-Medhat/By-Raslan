<?php

namespace App\Modules\Returns\Http\Controllers;

use App\Modules\Returns\Models\SalesReturn;
use App\Modules\Returns\Services\ReturnService;
use App\Modules\Sales\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReturnController
{
    public function __construct(private readonly ReturnService $returns)
    {
    }

    public function index(): View
    {
        $returns = SalesReturn::with('sale')->orderByDesc('id')->paginate(20);

        return view('returns.index', compact('returns'));
    }

    public function create(): View
    {
        $sales = Sale::orderByDesc('id')->take(50)->get();

        return view('returns.create', compact('sales'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sale_id' => ['required', 'exists:sales,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->returns->request(Sale::findOrFail($data['sale_id']), $data['reason'] ?? null, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['return' => $e->getMessage()]);
        }

        return redirect()->route('returns.index')->with('status', 'تم تسجيل المرتجع');
    }

    public function approve(Request $request, SalesReturn $return): RedirectResponse
    {
        try {
            $this->returns->approve($return, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['return' => $e->getMessage()]);
        }

        return back()->with('status', 'تمت الموافقة وإعادة المخزون');
    }

    public function reject(Request $request, SalesReturn $return): RedirectResponse
    {
        try {
            $this->returns->reject($return, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['return' => $e->getMessage()]);
        }

        return back()->with('status', 'تم رفض المرتجع');
    }
}