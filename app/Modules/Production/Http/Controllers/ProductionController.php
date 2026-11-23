<?php

namespace App\Modules\Production\Http\Controllers;

use App\Modules\Catalog\Models\Item;
use App\Modules\Organization\Models\Location;
use App\Modules\Production\Models\ProdOrder;
use App\Modules\Production\Services\ProductionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionController
{
    public function __construct(private readonly ProductionService $production)
    {
    }

    public function index(): View
    {
        $orders = ProdOrder::with('item')->withCount('steps')->orderByDesc('id')->get();

        return view('production.index', compact('orders'));
    }

    public function create(): View
    {
        $items = Item::orderBy('name')->get();
        $sections = Location::where('type', 'section')->orderBy('sort')->get();

        return view('production.create', compact('items', 'sections'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'qty' => ['required', 'numeric', 'min:0.001'],
            'due' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $order = $this->production->createOrder((int) $data['item_id'], (float) $data['qty'], $data['due'] ?? null, $data['note'] ?? null, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()->route('production.show', $order)->with('status', 'تم إنشاء أمر الإنتاج');
    }

    public function show(ProdOrder $order): View
    {
        $order->load(['steps.section', 'item']);

        return view('production.show', compact('order'));
    }

    public function start(Request $request, ProdOrder $order, int $step): RedirectResponse
    {
        $data = $request->validate(['in_weight' => ['required', 'numeric', 'min:0.001']]);

        try {
            $this->production->startStep($order->id, $step, (float) $data['in_weight'], auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['step' => $e->getMessage()]);
        }

        return back()->with('status', 'بدأت الغرفة');
    }

    public function finish(Request $request, ProdOrder $order, int $step): RedirectResponse
    {
        $data = $request->validate([
            'out_weight' => ['required', 'numeric', 'min:0.001'],
            'waste' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $this->production->finishStep($order->id, $step, (float) $data['out_weight'], (float) ($data['waste'] ?? 0), auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['step' => $e->getMessage()]);
        }

        return back()->with('status', 'تم إنهاء الغرفة');
    }

    public function setRoute(Request $request, Item $item): RedirectResponse
    {
        $data = $request->validate([
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['string', 'exists:locations,id'],
        ]);

        $this->production->setRoute($item->id, $data['sections']);

        return back()->with('status', 'تم حفظ مشوار الغرف');
    }

    public function routes(): View
    {
        $items = Item::orderBy('name')->get();
        $sections = Location::where('type', 'section')->orderBy('sort')->get();

        return view('production.routes', compact('items', 'sections'));
    }

    public function station(): View
    {
        $room = (string) request('room', '');
        $sections = Location::where('type', 'section')->where('active', true)->orderBy('sort')->get();
        $jobs = collect();
        if ($room !== '') {
            $orders = ProdOrder::with(['item', 'steps'])->whereIn('status', ['planned', 'running'])->orderBy('due')->orderBy('id')->get();
            foreach ($orders as $o) {
                $steps = $o->steps->sortBy('step')->values();
                $cur = $steps->firstWhere(fn ($s) => $s->status !== 'done');
                if (! $cur || $cur->section_id !== $room) continue;
                $prev = $steps->filter(fn ($s) => $s->step < $cur->step)->last();
                $jobs->push((object) [
                    'order' => $o->id, 'no' => $o->no, 'step' => $cur->step,
                    'item' => $o->item?->name ?? '', 'unit' => $o->item?->unit ?? '',
                    'qty' => $o->qty, 'status' => $cur->status,
                    'in_weight' => $cur->in_weight, 'prev_out' => $prev?->out_weight,
                    'last' => $steps->last()?->step === $cur->step,
                ]);
            }
        }
        return view('production.station', compact('sections', 'jobs', 'room'));
    }

    public function packPage(): View
    {
        $orders = ProdOrder::whereIn('status', ['planned', 'running'])->with('item')->orderByDesc('id')->get();

        return view('pack.index', compact('orders'));
    }

    public function pack(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'order_id' => ['nullable', 'exists:prod_orders,id'],
        ]);

        try {
            $box = $this->production->packBox($data['code'], isset($data['order_id']) ? (int) $data['order_id'] : null, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['pack' => $e->getMessage()]);
        }

        return back()->with('status', 'تم تغليف علبة #'.$box->sn);
    }

    public function packFinish(Request $request): RedirectResponse
    {
        $data = $request->validate(['order_id' => ['required', 'exists:prod_orders,id']]);

        try {
            $message = $this->production->packFinish((int) $data['order_id'], auth()->id());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['pack' => $e->getMessage()]);
        }

        return back()->with('status', $message);
    }
}