<?php

namespace App\Modules\Returns\Http\Controllers;

use App\Modules\Organization\Models\Location;
use App\Modules\Returns\Models\SalesReturn;
use App\Modules\Returns\Services\ReturnService;
use App\Modules\Sales\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReturnController
{
    public function __construct(private readonly ReturnService $returns) {}

    public function index(): View
    {
        $returns = SalesReturn::with('sale')->orderByDesc('id')->paginate(20);
        return view('returns.index', compact('returns'));
    }

    public function create(Request $request): View
    {
        $saleNo = $request->query('sale_no');
        $sale = null;
        if ($saleNo) {
            $sale = Sale::with(['lines.item','lines' => fn($q)=>$q->orderBy('line')])->where('no', $saleNo)->first();
        }
        $locations = Location::where('active', true)->whereIn('type', ['dark','shop','factory'])->orderBy('name')->get(['id','name','type']);
        return view('returns.create', compact('sale','saleNo','locations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sale_id' => ['required', 'exists:sales,id'],
            'line_ids' => ['nullable', 'array'],
            'line_ids.*' => ['integer', 'exists:sale_lines,id'],
            'destination' => ['nullable', 'in:stock,waste,donated'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'refund_method' => ['nullable', 'in:cash,card,none'],
            'reason' => ['nullable', 'string', 'max:255'],
            'items' => ['sometimes', 'array'],
            'items.*.selected' => ['nullable'],
            'items.*.sale_line_id' => ['required', 'exists:sale_lines,id'],
            'items.*.destination' => ['required', 'in:stock,waste,donation'],
            'items.*.location_id' => ['nullable', 'exists:locations,id'],
        ]);

        $items = collect($data['items'] ?? [])->filter(fn($i)=>!empty($i['selected']))->values()->all();
        if (array_key_exists('items', $data) && empty($items)) {
            return back()->withErrors(['return' => 'اختر صنفًا أو طبقًا واحدًا على الأقل.'])->withInput();
        }

        try {
            $sale = Sale::findOrFail($data['sale_id']);
            $this->returns->request($sale, $data['reason'] ?? null, $request->user()->id, $items);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['return' => $e->getMessage()])->withInput();
        }

        return redirect()->route('returns.index')->with('status', 'تم تسجيل المرتجع');
    }

    public function approve(Request $request, SalesReturn $return): RedirectResponse
    {
        try { $this->returns->approve($return, $request->user()->id); }
        catch (\RuntimeException $e) { return back()->withErrors(['return' => $e->getMessage()]); }
        return back()->with('status', 'تمت الموافقة وإعادة المخزون');
    }

    public function reject(Request $request, SalesReturn $return): RedirectResponse
    {
        try { $this->returns->reject($return, $request->user()->id); }
        catch (\RuntimeException $e) { return back()->withErrors(['return' => $e->getMessage()]); }
        return back()->with('status', 'تم رفض المرتجع');
    }
}
