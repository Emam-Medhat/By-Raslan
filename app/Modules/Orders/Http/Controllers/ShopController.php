<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Modules\Orders\Services\OrderService;
use Illuminate\View\View;

/**
 * Public customer pages (no login): menu/cart/checkout + order tracking.
 * These are the Blade equivalents of the legacy public order page + track page.
 */
class ShopController
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    public function index(): View
    {
        $branches = $this->orders->branches();

        return view('shop.index', compact('branches'));
    }

    public function track(string $token): View
    {
        try {
            $order = $this->orders->track($token);
        } catch (\Throwable $e) {
            abort(404);
        }

        return view('shop.track', compact('order'));
    }
}