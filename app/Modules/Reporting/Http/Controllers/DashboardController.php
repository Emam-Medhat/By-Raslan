<?php

namespace App\Modules\Reporting\Http\Controllers;

use App\Modules\Accounting\Models\Expense;
use App\Modules\Inventory\Models\Box;
use App\Modules\Orders\Models\OnlineOrder;
use App\Modules\Sales\Models\Sale;
use Illuminate\View\View;

class DashboardController
{
    public function index(): View
    {
        $today = now()->toDateString();

        $stats = [
            'sales_today' => round((float) Sale::whereDate('at', $today)->sum('total'), 2),
            'orders_today' => OnlineOrder::whereDate('created_at', $today)->count(),
            'expenses_today' => round((float) Expense::whereDate('day', $today)->where('status', 'ok')->sum('amount'), 2),
            'boxes_in_stock' => Box::where('status', 'stock')->count(),
        ];

        $recentSales = Sale::with('cashier')->orderByDesc('id')->take(5)->get();
        $recentOrders = OnlineOrder::with('customer')->orderByDesc('id')->take(5)->get();

        return view('dashboard', compact('stats', 'recentSales', 'recentOrders'));
    }
}