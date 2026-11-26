<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Modules\Orders\Models\OnlineOrder;
use App\Modules\Orders\Services\OrderCollectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderCollectionController
{
    public function __construct(private readonly OrderCollectionService $collections) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $q = OnlineOrder::with(['customer','location'])->orderByDesc('id');
        $user = $request->user();
        if ($user && !$user->isOwner()) {
            $ids = $user->accessibleLocationIds();
            if (!empty($ids)) $q->whereIn('location_id', $ids);
        }
        if ($status === 'pending_collection') $q->whereNull('collected_at')->whereIn('status',['out','delivered']);
        elseif ($status === 'collected') $q->whereNotNull('collected_at');
        elseif ($status !== '') $q->where('status', $status);
        $orders = $q->paginate(30)->withQueryString();
        return view('orders.collections', compact('orders','status'));
    }

    public function store(Request $request, OnlineOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'method' => ['required','in:cash,instapay,visa,wallet'],
            'amount' => ['required','numeric','min:0'],
            'delivered' => ['nullable','boolean'],
        ]);

        try {
            $this->collections->collect(
                $order,
                $data['method'],
                (float) $data['amount'],
                $request->user()->id,
                $request->boolean('delivered')
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['collection' => $e->getMessage()]);
        }

        return back()->with('status', 'تم تسجيل التحصيل بنجاح.');
    }
}
