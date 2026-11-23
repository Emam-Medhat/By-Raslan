<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Modules\Notifications\Services\NotificationService;
use App\Modules\Orders\Models\OnlineOrder;
use App\Modules\Orders\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly NotificationService $notifications,
    ) {
    }

    public function index(): View
    {
        $status = request('status');
        $orders = OnlineOrder::with('customer')->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')->paginate(20);

        return view('orders.index', compact('orders'));
    }

    public function show(OnlineOrder $order): View
    {
        $order->load(['lines.item', 'boxes', 'customer']);

        return view('orders.show', compact('order'));
    }

    public function status(Request $request, OnlineOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:accepted,preparing,ready,out,delivered,cancelled'],
            'driver' => ['nullable', 'string', 'max:120'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['status'] === 'cancelled') {
            abort_unless($request->user()->hasPermission('orders.cancel'), 403, 'الإلغاء محتاج صلاحية');
        }

        try {
            $this->orders->setStatus($order, $data['status'], $data['driver'] ?? null, $data['reason'] ?? null, $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        $this->notifications->orderStatus($order->fresh());

        return back()->with('status', 'تم تحديث حالة الطلب');
    }

    public function scan(Request $request, OnlineOrder $order): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'qty' => ['nullable', 'numeric'],
        ]);

        try {
            $name = $this->orders->scan($order, $data['code'], isset($data['qty']) ? (float) $data['qty'] : null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['scan' => $e->getMessage()]);
        }

        return back()->with('status', 'تم تحضير: '.$name);
    }

    public function unscan(Request $request, OnlineOrder $order): RedirectResponse
    {
        $data = $request->validate(['box_sn' => ['required', 'integer']]);

        try {
            $this->orders->unscan($order, (int) $data['box_sn']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['scan' => $e->getMessage()]);
        }

        return back()->with('status', 'تم التراجع');
    }

    public function deliver(OnlineOrder $order): RedirectResponse
    {
        try {
            $total = $this->orders->deliver($order);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', 'تم التسليم والتحصيل — الإجمالي: '.$total);
    }
}