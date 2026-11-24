<?php

namespace App\Modules\Reporting\Http\Controllers;

use App\Modules\Accounting\Models\Expense;
use App\Modules\Accounting\Models\ExpenseCategory;
use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Orders\Models\OnlineOrder;
use App\Modules\Organization\Models\Location;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Purchasing\Models\SupplierPayment;
use App\Modules\Production\Models\ProdOrder;
use App\Modules\Production\Models\Trip;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\Shift;
use App\Modules\Transfers\Models\Transfer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StatisticsController
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Global filters
        $range = $request->string('range', '30d')->toString();
        $locationId = $request->string('location_id')->toString();
        $categoryId = $request->string('category_id')->toString();
        $supplierId = $request->string('supplier_id')->toString();
        $customerId = $request->string('customer_id')->toString();
        $cashierId = $request->string('cashier_id')->toString();

        $locations = $this->visibleLocations($user);
        $visibleIds = $locations->pluck('id')->all();
        $canChoose = $user->isOwner() || $user->hasPermission('acc.view');

        if ($locationId && (!$canChoose || !in_array($locationId, $visibleIds, true))) {
            $locationId = '';
        } elseif (!$canChoose && !$locationId) {
            $locationId = $user->home_location_id ?? '';
        }

        [$from, $to] = $this->resolveRange($range, $request);
        $days = max(1, $from->diffInDays($to) + 1);
        $prevDays = $days;
        $prevTo = $from->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subDays($prevDays - 1)->startOfDay();

        // Base queries with filters
        $salesBase = Sale::query()->whereBetween('sales.at', [$from, $to]);
        $prevSalesBase = Sale::query()->whereBetween('sales.at', [$prevFrom, $prevTo]);
        $expensesBase = Expense::query()->where('status', 'ok')->whereBetween('expenses.day', [$from->toDateString(), $to->toDateString()]);
        $prevExpensesBase = Expense::query()->where('status', 'ok')->whereBetween('expenses.day', [$prevFrom->toDateString(), $prevTo->toDateString()]);
        $onlineBase = OnlineOrder::query()->whereBetween('online_orders.created_at', [$from, $to]);
        $poBase = PurchaseOrder::query()->whereBetween('purchase_orders.created_at', [$from, $to]);
        $paymentsBase = SupplierPayment::query()->whereBetween('supplier_payments.created_at', [$from, $to]);
        $prodBase = ProdOrder::query()->whereBetween('prod_orders.created_at', [$from, $to]);
        $shiftBase = Shift::query()->whereBetween('shifts.opened_at', [$from, $to]);
        $transferBase = Transfer::query()->whereBetween('transfers.created_at', [$from, $to]);
        $moveBase = StockMove::query()->whereBetween('stock_moves.at', [$from, $to]);

        if ($locationId) {
            $salesBase->where('location_id', $locationId);
            $prevSalesBase->where('location_id', $locationId);
            $expensesBase->where('location_id', $locationId);
            $prevExpensesBase->where('location_id', $locationId);
            $onlineBase->where('location_id', $locationId);
            $poBase->where('location_id', $locationId);
            $prodBase->where('location_id', $locationId);
            $shiftBase->where('location_id', $locationId);
            $transferBase->where(function($q) use ($locationId) {
                $q->where('from_loc', $locationId)->orWhere('to_loc', $locationId);
            });
            $moveBase->where('location_id', $locationId);
        }

        if ($categoryId) {
            $expensesBase->where('category_id', $categoryId);
            $prevExpensesBase->where('category_id', $categoryId);
        }

        if ($supplierId) {
            $poBase->where('supplier_id', $supplierId);
        }

        if ($cashierId) {
            $salesBase->where('cashier', $cashierId);
            $prevSalesBase->where('cashier', $cashierId);
            $shiftBase->where('cashier', $cashierId);
        }

        // ===== SALES ANALYTICS =====
        $salesAnalytics = $this->getSalesAnalytics($salesBase, $prevSalesBase, $from, $to, $prevFrom, $prevTo, $days, $locationId);

        // ===== EXPENSES ANALYTICS =====
        $expensesAnalytics = $this->getExpensesAnalytics($expensesBase, $prevExpensesBase, $from, $to, $prevFrom, $prevTo, $days, $locationId);

        // ===== PURCHASING ANALYTICS =====
        $purchasingAnalytics = $this->getPurchasingAnalytics($poBase, $paymentsBase, $from, $to, $locationId);

        // ===== INVENTORY ANALYTICS =====
        $inventoryAnalytics = $this->getInventoryAnalytics($moveBase, $from, $to, $locationId, $visibleIds);

        // ===== ONLINE ORDERS ANALYTICS =====
        $onlineAnalytics = $this->getOnlineAnalytics($onlineBase, $from, $to, $locationId);

        // ===== PRODUCTION ANALYTICS =====
        $productionAnalytics = $this->getProductionAnalytics($prodBase, $from, $to, $locationId);

        // ===== SHIFTS ANALYTICS =====
        $shiftsAnalytics = $this->getShiftsAnalytics($shiftBase, $from, $to, $locationId);

        // ===== TRANSFERS ANALYTICS =====
        $transfersAnalytics = $this->getTransfersAnalytics($transferBase, $from, $to, $locationId);

        // ===== CUSTOMERS ANALYTICS =====
        $customersAnalytics = $this->getCustomersAnalytics($salesBase, $onlineBase, $from, $to, $locationId);

        // ===== SUPPLIERS ANALYTICS =====
        $suppliersAnalytics = $this->getSuppliersAnalytics($poBase, $paymentsBase, $from, $to, $locationId);

        // ===== BRANCHES ANALYTICS =====
        $branchesAnalytics = $this->getBranchesAnalytics($locations, $from, $to, $canChoose);

        // ===== USERS ANALYTICS =====
        $usersAnalytics = $this->getUsersAnalytics($salesBase, $shiftBase, $from, $to, $locationId);

        // ===== CATEGORIES ANALYTICS =====
        $categoriesAnalytics = $this->getCategoriesAnalytics($from, $to, $locationId);

        // Build chart data for main chart
        $mainChart = $this->buildMainChart($from, $to, $locationId, $days);

        // Filter options
        $categories = ExpenseCategory::query()->orderBy('name')->get(['id','name']);
        $suppliers = Supplier::query()->where('active', 1)->orderBy('name')->get(['id','name']);
        $customers = DB::table('customers')->orderBy('name')->get(['id','name']);
        $cashiers = DB::table('users')->where('active', 1)->orderBy('name')->get(['id','name']);

        return view('statistics.index', [
            'range' => $range,
            'from' => $from,
            'to' => $to,
            'locationId' => $locationId,
            'categoryId' => $categoryId,
            'supplierId' => $supplierId,
            'customerId' => $customerId,
            'cashierId' => $cashierId,
            'locations' => $locations,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'customers' => $customers,
            'cashiers' => $cashiers,
            'mainChart' => $mainChart,
            'salesAnalytics' => $salesAnalytics,
            'expensesAnalytics' => $expensesAnalytics,
            'purchasingAnalytics' => $purchasingAnalytics,
            'inventoryAnalytics' => $inventoryAnalytics,
            'onlineAnalytics' => $onlineAnalytics,
            'productionAnalytics' => $productionAnalytics,
            'shiftsAnalytics' => $shiftsAnalytics,
            'transfersAnalytics' => $transfersAnalytics,
            'customersAnalytics' => $customersAnalytics,
            'suppliersAnalytics' => $suppliersAnalytics,
            'branchesAnalytics' => $branchesAnalytics,
            'usersAnalytics' => $usersAnalytics,
            'categoriesAnalytics' => $categoriesAnalytics,
            'periodLabel' => $this->getPeriodLabel($range),
            'canChooseLocation' => $canChoose,
        ]);
    }

    private function resolveRange(string $range, Request $request): array
    {
        $now = Carbon::now();
        $from = $now->copy()->subDays(29)->startOfDay();
        $to = $now->copy()->endOfDay();

        if ($range === 'today') {
            $from = $now->copy()->startOfDay(); $to = $now->copy()->endOfDay();
        } elseif ($range === '7d') {
            $from = $now->copy()->subDays(6)->startOfDay();
        } elseif ($range === '30d') {
            $from = $now->copy()->subDays(29)->startOfDay();
        } elseif ($range === '90d') {
            $from = $now->copy()->subDays(89)->startOfDay();
        } elseif ($range === 'month') {
            $from = $now->copy()->startOfMonth(); $to = $now->copy()->endOfMonth();
        } elseif ($range === 'prev_month') {
            $from = $now->copy()->subMonthNoOverflow()->startOfMonth(); $to = $now->copy()->subMonthNoOverflow()->endOfMonth();
        } elseif ($range === 'year') {
            $from = $now->copy()->startOfYear(); $to = $now->copy()->endOfYear();
        } elseif ($range === 'custom') {
            try {
                $from = Carbon::parse($request->input('from', $from->toDateString()))->startOfDay();
                $to = Carbon::parse($request->input('to', $to->toDateString()))->endOfDay();
                if ($from->gt($to)) [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            } catch (\Throwable $e) {
                $range = '30d';
            }
        }

        return [$from, $to];
    }

    private function getPeriodLabel(string $range): string
    {
        return match($range) {
            'today' => 'اليوم',
            '7d' => 'آخر 7 أيام',
            '30d' => 'آخر 30 يوماً',
            '90d' => 'آخر 90 يوماً',
            'month' => 'الشهر الحالي',
            'prev_month' => 'الشهر السابق',
            'year' => 'السنة الحالية',
            'custom' => 'فترة مخصصة',
            default => 'آخر 30 يوماً',
        };
    }

    private function visibleLocations($user)
    {
        $query = Location::query()->orderBy('sort')->orderBy('name');
        if ($user->isOwner() || $user->hasPermission('acc.view')) {
            return $query->get(['id','name','type','active','city']);
        }
        if ($user->home_location_id) {
            return $query->where('id', $user->home_location_id)->get(['id','name','type','active','city']);
        }
        return collect();
    }

    private function changePercent(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.00001) return $current > 0 ? 100.0 : null;
        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    private function getSalesAnalytics($salesBase, $prevSalesBase, Carbon $from, Carbon $to, Carbon $prevFrom, Carbon $prevTo, int $days, ?string $locationId): array
    {
        $total = (float) (clone $salesBase)->sum('total');
        $prevTotal = (float) (clone $prevSalesBase)->sum('total');
        $count = (int) (clone $salesBase)->count();
        $prevCount = (int) (clone $prevSalesBase)->count();
        $avgTicket = $count > 0 ? $total / $count : 0;
        $prevAvgTicket = $prevCount > 0 ? $prevTotal / $prevCount : 0;

        // Daily breakdown
        $daily = [];
        $labels = []; $salesData = []; $invoicesData = [];
        $currentRows = (clone $salesBase)->selectRaw('DATE(at) as d, SUM(total) as total, COUNT(*) as count')->groupBy('d')->pluck('total', 'd');
        $countRows = (clone $salesBase)->selectRaw('DATE(at) as d, COUNT(*) as count')->groupBy('d')->pluck('count', 'd');
        for ($i = 0; $i < $days; $i++) {
            $d = $from->copy()->addDays($i)->toDateString();
            $s = round((float) ($currentRows[$d] ?? 0), 2);
            $c = (int) ($countRows[$d] ?? 0);
            $labels[] = Carbon::parse($d)->format('m/d');
            $salesData[] = $s;
            $invoicesData[] = $c;
            $daily[] = ['day' => $d, 'sales' => $s, 'invoices' => $c, 'avg' => $c > 0 ? round($s/$c, 2) : 0];
        }

        // Top items
        $topItems = (clone $salesBase)
            ->join('sale_lines', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('items', 'sale_lines.item_id', '=', 'items.id')
            ->selectRaw('items.id, items.name, SUM(sale_lines.qty) as total_qty, SUM(sale_lines.total) as total_sales, COUNT(DISTINCT sales.id) as orders')
            ->groupBy('items.id', 'items.name')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get()
            ->map(fn($r) => ['name' => $r->name, 'qty' => round($r->total_qty, 2), 'sales' => round($r->total_sales, 2), 'orders' => $r->orders])
            ->values();

        // Top customers - disabled as Sale model doesn't have customer_id
        $topCustomers = collect();

        // Payment methods - disabled as Sale model doesn't have payment_method
        $paymentMethods = collect();

        // Hourly distribution
        $hourly = (clone $salesBase)
            ->selectRaw('HOUR(at) as hour, SUM(total) as total, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(fn($r) => ['hour' => $r->hour, 'total' => round($r->total, 2), 'count' => $r->count])
            ->values();

        return [
            'total' => $total,
            'prev_total' => $prevTotal,
            'change' => $this->changePercent($total, $prevTotal),
            'count' => $count,
            'prev_count' => $prevCount,
            'avg_ticket' => round($avgTicket, 2),
            'prev_avg_ticket' => round($prevAvgTicket, 2),
            'avg_ticket_change' => $this->changePercent($avgTicket, $prevAvgTicket),
            'daily' => $daily,
            'chart_labels' => $labels,
            'chart_sales' => $salesData,
            'chart_invoices' => $invoicesData,
            'top_items' => $topItems,
            'top_customers' => $topCustomers,
            'payment_methods' => $paymentMethods,
            'hourly' => $hourly,
        ];
    }

    private function getExpensesAnalytics($expensesBase, $prevExpensesBase, Carbon $from, Carbon $to, Carbon $prevFrom, Carbon $prevTo, int $days, ?string $locationId): array
    {
        $total = (float) (clone $expensesBase)->sum('amount');
        $prevTotal = (float) (clone $prevExpensesBase)->sum('amount');
        $count = (int) (clone $expensesBase)->count();

        // By category
        $byCategory = (clone $expensesBase)
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->selectRaw('expense_categories.name as category, SUM(expenses.amount) as total, COUNT(*) as count')
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => ['category' => $r->category, 'total' => round($r->total, 2), 'count' => $r->count, 'pct' => $total > 0 ? round($r->total / $total * 100, 1) : 0])
            ->values();

        // Daily
        $daily = [];
        $labels = []; $expensesData = [];
        $currentRows = (clone $expensesBase)->selectRaw('DATE(day) as d, SUM(amount) as total')->groupBy('d')->pluck('total', 'd');
        for ($i = 0; $i < $days; $i++) {
            $d = $from->copy()->addDays($i)->toDateString();
            $e = round((float) ($currentRows[$d] ?? 0), 2);
            $labels[] = Carbon::parse($d)->format('m/d');
            $expensesData[] = $e;
            $daily[] = ['day' => $d, 'expenses' => $e];
        }

        // Top vendors - disabled as Expense model doesn't have vendor column
        $topVendors = collect();

        return [
            'total' => $total,
            'prev_total' => $prevTotal,
            'change' => $this->changePercent($total, $prevTotal),
            'count' => $count,
            'by_category' => $byCategory,
            'daily' => $daily,
            'chart_labels' => $labels,
            'chart_expenses' => $expensesData,
            'top_vendors' => $topVendors,
        ];
    }

    private function getPurchasingAnalytics($poBase, $paymentsBase, Carbon $from, Carbon $to, ?string $locationId): array
    {
        // Calculate PO total from lines
        $poTotal = (float) (clone $poBase)
            ->join('po_lines', 'purchase_orders.id', '=', 'po_lines.po_id')
            ->selectRaw('COALESCE(SUM(po_lines.qty * po_lines.price),0) as total')
            ->value('total');
        $poCount = (int) (clone $poBase)->count();
        $paymentsTotal = (float) (clone $paymentsBase)->sum('amount');

        // By status
        $byStatus = (clone $poBase)
            ->join('po_lines', 'purchase_orders.id', '=', 'po_lines.po_id')
            ->selectRaw('purchase_orders.status, COUNT(DISTINCT purchase_orders.id) as count, COALESCE(SUM(po_lines.qty * po_lines.price),0) as total')
            ->groupBy('purchase_orders.status')
            ->orderByDesc('total')
            ->get()
            ->map(fn($r) => ['status' => $r->status, 'count' => $r->count, 'total' => round($r->total, 2)])
            ->values();

        // Top suppliers
        $topSuppliers = (clone $poBase)
            ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->join('po_lines', 'purchase_orders.id', '=', 'po_lines.po_id')
            ->selectRaw('suppliers.name, COUNT(DISTINCT purchase_orders.id) as orders, COALESCE(SUM(po_lines.qty * po_lines.price),0) as total')
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn($r) => ['name' => $r->name, 'orders' => $r->orders, 'total' => round($r->total, 2)])
            ->values();

        // Payment vs PO
        $paymentRatio = $poTotal > 0 ? round($paymentsTotal / $poTotal * 100, 1) : 0;

        return [
            'po_total' => $poTotal,
            'po_count' => $poCount,
            'payments_total' => $paymentsTotal,
            'payment_ratio' => $paymentRatio,
            'by_status' => $byStatus,
            'top_suppliers' => $topSuppliers,
        ];
    }

    private function getInventoryAnalytics($moveBase, Carbon $from, Carbon $to, ?string $locationId, array $visibleIds): array
    {
        // Stock value
        $stockValue = (float) (clone $moveBase)
            ->where('kind', 'receive')
            ->selectRaw('COALESCE(SUM(ABS(qty) * COALESCE(unit_cost,0)),0) as v')->value('v');

        // Movement by type
        $byType = (clone $moveBase)
            ->selectRaw('kind, SUM(ABS(qty)) as total_qty, COUNT(*) as moves')
            ->groupBy('kind')
            ->orderByDesc('total_qty')
            ->get()
            ->map(fn($r) => ['type' => $r->kind, 'qty' => round($r->total_qty, 3), 'moves' => $r->moves])
            ->values();

        // Top moving items
        $topItems = (clone $moveBase)
            ->join('items', 'stock_moves.item_id', '=', 'items.id')
            ->selectRaw('items.name, SUM(ABS(stock_moves.qty)) as total_qty, COUNT(*) as moves')
            ->groupBy('items.id', 'items.name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get()
            ->map(fn($r) => ['name' => $r->name, 'qty' => round($r->total_qty, 3), 'moves' => $r->moves])
            ->values();

        // Low stock
        $balances = StockMove::query()
            ->selectRaw('item_id, location_id, SUM(qty) as qty')
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->when(!$locationId && !empty($visibleIds), fn($q) => $q->whereIn('location_id', $visibleIds))
            ->groupBy('item_id', 'location_id')
            ->get();
        $byItem = [];
        foreach ($balances as $b) {
            $byItem[$b->item_id] = ($byItem[$b->item_id] ?? 0) + (float) $b->qty;
        }
        $items = DB::table('items')->where('active', 1)->get(['id','name','unit']);
        $low = []; $out = [];
        foreach ($items as $item) {
            $qty = round((float) ($byItem[$item->id] ?? 0), 3);
            if ($qty <= 0) $out[] = ['name' => $item->name, 'unit' => $item->unit, 'qty' => $qty];
            elseif ($qty <= 5) $low[] = ['name' => $item->name, 'unit' => $item->unit, 'qty' => $qty];
        }
        usort($low, fn($a,$b) => $a['qty'] <=> $b['qty']);
        usort($out, fn($a,$b) => $a['qty'] <=> $b['qty']);

        // Boxes
        $boxesStock = (int) Box::query()->where('status', 'stock')->when($locationId, fn($q) => $q->where('location_id', $locationId))->count();
        $boxesTransit = (int) Box::query()->where('status', 'transit')->when($locationId, fn($q) => $q->where('location_id', $locationId))->count();
        $boxesProduced = (int) Box::query()->where('status', 'produced')->when($locationId, fn($q) => $q->where('location_id', $locationId))->count();

        return [
            'stock_value' => $stockValue,
            'by_type' => $byType,
            'top_items' => $topItems,
            'low_stock' => array_slice($low, 0, 10),
            'out_of_stock' => array_slice($out, 0, 10),
            'low_count' => count($low),
            'out_count' => count($out),
            'boxes_stock' => $boxesStock,
            'boxes_transit' => $boxesTransit,
            'boxes_produced' => $boxesProduced,
        ];
    }

    private function getOnlineAnalytics($onlineBase, Carbon $from, Carbon $to, ?string $locationId): array
    {
        $total = (float) (clone $onlineBase)->sum('total');
        $count = (int) (clone $onlineBase)->count();

        // By status
        $byStatus = (clone $onlineBase)
            ->selectRaw('status, COUNT(*) as count, SUM(total) as total')
            ->groupBy('status')
            ->orderByDesc('count')
            ->get()
            ->map(fn($r) => ['status' => $r->status, 'count' => $r->count, 'total' => round($r->total, 2)])
            ->values();

        // Daily
        $days = $from->diffInDays($to) + 1;
        $daily = [];
        $labels = []; $ordersData = []; $revenueData = [];
        $currentRows = (clone $onlineBase)->selectRaw('DATE(created_at) as d, COUNT(*) as count, SUM(total) as total')->groupBy('d')->get()->keyBy('d');
        for ($i = 0; $i < $days; $i++) {
            $d = $from->copy()->addDays($i)->toDateString();
            $c = (int) ($currentRows[$d]->count ?? 0);
            $r = round((float) ($currentRows[$d]->total ?? 0), 2);
            $labels[] = Carbon::parse($d)->format('m/d');
            $ordersData[] = $c;
            $revenueData[] = $r;
            $daily[] = ['day' => $d, 'orders' => $c, 'revenue' => $r];
        }

        // Avg order value
        $avgOrder = $count > 0 ? round($total / $count, 2) : 0;

        return [
            'total' => $total,
            'count' => $count,
            'avg_order' => $avgOrder,
            'by_status' => $byStatus,
            'daily' => $daily,
            'chart_labels' => $labels,
            'chart_orders' => $ordersData,
            'chart_revenue' => $revenueData,
        ];
    }

    private function getProductionAnalytics($prodBase, Carbon $from, Carbon $to, ?string $locationId): array
    {
        $total = (int) (clone $prodBase)->count();
        $completed = (int) (clone $prodBase)->where('status', 'completed')->count();
        $running = (int) (clone $prodBase)->where('status', 'running')->count();
        $planned = (int) (clone $prodBase)->where('status', 'planned')->count();
        $cancelled = (int) (clone $prodBase)->where('status', 'cancelled')->count();

        // By product
        $byProduct = (clone $prodBase)
            ->join('items', 'prod_orders.item_id', '=', 'items.id')
            ->selectRaw('items.name, COUNT(*) as orders, SUM(prod_orders.qty) as total_qty')
            ->groupBy('items.id', 'items.name')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get()
            ->map(fn($r) => ['name' => $r->name, 'orders' => $r->orders, 'qty' => round($r->total_qty, 2)])
            ->values();

        // Daily completion
        $days = $from->diffInDays($to) + 1;
        $daily = [];
        $labels = []; $completedData = [];
        $currentRows = (clone $prodBase)->where('status', 'done')->selectRaw('DATE(due) as d, COUNT(*) as count')->groupBy('d')->pluck('count', 'd');
        for ($i = 0; $i < $days; $i++) {
            $d = $from->copy()->addDays($i)->toDateString();
            $c = (int) ($currentRows[$d] ?? 0);
            $labels[] = Carbon::parse($d)->format('m/d');
            $completedData[] = $c;
            $daily[] = ['day' => $d, 'completed' => $c];
        }

        return [
            'total' => $total,
            'completed' => $completed,
            'running' => $running,
            'planned' => $planned,
            'cancelled' => $cancelled,
            'completion_rate' => $total > 0 ? round($completed / $total * 100, 1) : 0,
            'by_product' => $byProduct,
            'daily' => $daily,
            'chart_labels' => $labels,
            'chart_completed' => $completedData,
        ];
    }

    private function getShiftsAnalytics($shiftBase, Carbon $from, Carbon $to, ?string $locationId): array
    {
        $totalShifts = (int) (clone $shiftBase)->count();
        $closedShifts = (int) (clone $shiftBase)->where('status', 'closed')->count();
        $openShifts = (int) (clone $shiftBase)->where('status', 'open')->count();
        
        // Calculate total sales from Sale model for closed shifts in this period
        $shiftIds = (clone $shiftBase)->where('status', 'closed')->pluck('id')->toArray();
        $totalSales = 0;
        if (!empty($shiftIds)) {
            $totalSales = (float) Sale::query()->whereIn('shift_id', $shiftIds)->sum('total');
        }
        
        $totalExpenses = (float) (clone $shiftBase)->where('status', 'closed')->sum('expenses_total');
        $totalCash = (float) (clone $shiftBase)->where('status', 'closed')->sum('counted_cash');
        $totalOpening = (float) (clone $shiftBase)->where('status', 'closed')->sum('opening_cash');
        $avgShiftSales = $closedShifts > 0 ? round($totalSales / $closedShifts, 2) : 0;

        // By cashier
        $byCashier = (clone $shiftBase)
            ->where('status', 'closed')
            ->join('users', 'shifts.cashier', '=', 'users.id')
            ->selectRaw('users.name, COUNT(*) as shifts, SUM(shifts.expenses_total) as expenses')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('shifts')
            ->limit(10)
            ->get()
            ->map(function ($r) use ($shiftIds) {
                $cashierSales = 0;
                if (!empty($shiftIds)) {
                    $cashierSales = (float) Sale::query()
                        ->whereIn('shift_id', $shiftIds)
                        ->where('cashier', $r->cashier ?? 0)
                        ->sum('total');
                }
                return ['name' => $r->name, 'shifts' => $r->shifts, 'sales' => round($cashierSales, 2), 'expenses' => round($r->expenses, 2)];
            })
            ->values();

        return [
            'total_shifts' => $totalShifts,
            'closed_shifts' => $closedShifts,
            'open_shifts' => $openShifts,
            'total_sales' => $totalSales,
            'total_expenses' => $totalExpenses,
            'total_cash' => $totalCash,
            'total_opening' => $totalOpening,
            'avg_shift_sales' => $avgShiftSales,
            'by_cashier' => $byCashier,
        ];
    }

    private function getTransfersAnalytics($transferBase, Carbon $from, Carbon $to, ?string $locationId): array
    {
        $total = (int) (clone $transferBase)->count();
        $pending = (int) (clone $transferBase)->where('status', 'pending')->count();
        $transit = (int) (clone $transferBase)->where('status', 'transit')->count();
        $received = (int) (clone $transferBase)->where('status', 'received')->count();
        $cancelled = (int) (clone $transferBase)->where('status', 'cancelled')->count();

        // By from location
        $fromLocations = (clone $transferBase)
            ->join('locations as l1', 'transfers.from_loc', '=', 'l1.id')
            ->selectRaw('l1.name, COUNT(*) as count')
            ->groupBy('l1.id', 'l1.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($r) => ['name' => $r->name, 'count' => $r->count])
            ->values();

        // By to location
        $toLocations = (clone $transferBase)
            ->join('locations as l2', 'transfers.to_loc', '=', 'l2.id')
            ->selectRaw('l2.name, COUNT(*) as count')
            ->groupBy('l2.id', 'l2.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn($r) => ['name' => $r->name, 'count' => $r->count])
            ->values();

        return [
            'total' => $total,
            'pending' => $pending,
            'transit' => $transit,
            'received' => $received,
            'cancelled' => $cancelled,
            'from_locations' => $fromLocations,
            'to_locations' => $toLocations,
        ];
    }

    private function getCustomersAnalytics($salesBase, $onlineBase, Carbon $from, Carbon $to, ?string $locationId): array
    {
        $totalCustomers = (int) DB::table('customers')->count();
        // activeCustomers disabled as Sale model doesn't have customer_id
        $activeCustomers = 0;

        $newCustomers = (int) DB::table('customers')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        // Top customers by sales - disabled as Sale model doesn't have customer_id
        $topCustomers = collect();

        return [
            'total' => $totalCustomers,
            'active' => $activeCustomers,
            'new' => $newCustomers,
            'top_customers' => $topCustomers,
        ];
    }

    private function getSuppliersAnalytics($poBase, $paymentsBase, Carbon $from, Carbon $to, ?string $locationId): array
    {
        $totalSuppliers = (int) Supplier::query()->where('active', 1)->count();
        $activeSuppliers = (int) (clone $poBase)
            ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->distinct('purchase_orders.supplier_id')
            ->count();

        // Top suppliers by PO value
        $topSuppliers = (clone $poBase)
            ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->join('po_lines', 'purchase_orders.id', '=', 'po_lines.po_id')
            ->selectRaw('suppliers.id, suppliers.name, COUNT(DISTINCT purchase_orders.id) as orders, COALESCE(SUM(po_lines.qty * po_lines.price),0) as total_po, SUM(supplier_payments.amount) as total_paid')
            ->leftJoin('supplier_payments', function($join) use ($from, $to) {
                $join->on('suppliers.id', '=', 'supplier_payments.supplier_id')
                    ->whereBetween('supplier_payments.created_at', [$from, $to]);
            })
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total_po')
            ->limit(10)
            ->get()
            ->map(fn($r) => [
                'name' => $r->name,
                'orders' => $r->orders,
                'total_po' => round($r->total_po, 2),
                'total_paid' => round($r->total_paid ?? 0, 2),
                'balance' => round(($r->total_po ?? 0) - ($r->total_paid ?? 0), 2),
            ])
            ->values();

        return [
            'total' => $totalSuppliers,
            'active' => $activeSuppliers,
            'top_suppliers' => $topSuppliers,
        ];
    }

    private function getBranchesAnalytics($locations, Carbon $from, Carbon $to, bool $canChoose): array
    {
        if (!$canChoose) return [];

        $rows = [];
        foreach ($locations as $loc) {
            $sales = (float) Sale::query()->where('location_id', $loc->id)->whereBetween('at', [$from, $to])->sum('total');
            $salesCount = (int) Sale::query()->where('location_id', $loc->id)->whereBetween('at', [$from, $to])->count();
            $expenses = (float) Expense::query()->where('location_id', $loc->id)->where('status','ok')->whereBetween('day', [$from->toDateString(), $to->toDateString()])->sum('amount');
            $online = (float) OnlineOrder::query()->where('location_id', $loc->id)->whereBetween('created_at', [$from, $to])->sum('total');
            $onlineCount = (int) OnlineOrder::query()->where('location_id', $loc->id)->whereBetween('created_at', [$from, $to])->count();
            $po = (float) PurchaseOrder::query()
                ->whereBetween('created_at', [$from, $to])
                ->join('po_lines', 'purchase_orders.id', '=', 'po_lines.po_id')
                ->selectRaw('COALESCE(SUM(po_lines.qty * po_lines.price),0) as total')
                ->value('total');
            $boxes = (int) Box::query()->where('location_id', $loc->id)->where('status','stock')->count();
            $openShift = Shift::query()->where('location_id', $loc->id)->where('status','open')->exists();

            $rows[] = [
                'id' => $loc->id,
                'name' => $loc->name,
                'type' => $loc->type,
                'city' => $loc->city,
                'sales' => $sales,
                'sales_count' => $salesCount,
                'expenses' => $expenses,
                'net' => $sales - $expenses,
                'online' => $online,
                'online_count' => $onlineCount,
                'purchasing' => $po,
                'boxes' => $boxes,
                'open_shift' => $openShift,
            ];
        }
        usort($rows, fn($a,$b) => $b['sales'] <=> $a['sales']);
        return $rows;
    }

    private function getUsersAnalytics($salesBase, $shiftBase, Carbon $from, Carbon $to, ?string $locationId): array
    {
        $totalUsers = (int) DB::table('users')->where('active', 1)->count();

        // Cashier performance
        $cashierPerf = (clone $salesBase)
            ->join('users', 'sales.cashier', '=', 'users.id')
            ->selectRaw('users.name, COUNT(*) as invoices, SUM(sales.total) as total_sales')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->get()
            ->map(fn($r) => ['name' => $r->name, 'invoices' => $r->invoices, 'sales' => round($r->total_sales, 2)])
            ->values();

        // Shift performance - calculate sales from Sale model
        $closedShiftIds = (clone $shiftBase)->where('status', 'closed')->pluck('id')->toArray();
        $shiftPerf = collect();
        if (!empty($closedShiftIds)) {
            $shiftPerf = DB::table('shifts')
                ->whereIn('shifts.id', $closedShiftIds)
                ->join('users', 'shifts.cashier', '=', 'users.id')
                ->selectRaw('users.name, users.id as user_id, COUNT(*) as shifts, SUM(shifts.expenses_total) as expenses')
                ->groupBy('users.id', 'users.name')
                ->orderByDesc('shifts')
                ->limit(10)
                ->get()
                ->map(function ($r) use ($closedShiftIds) {
                    $sales = (float) Sale::query()->whereIn('shift_id', $closedShiftIds)->where('cashier', $r->user_id)->sum('total');
                    return ['name' => $r->name, 'shifts' => $r->shifts, 'sales' => round($sales, 2), 'expenses' => round($r->expenses, 2)];
                })
                ->values();
        }

        return [
            'total_users' => $totalUsers,
            'cashier_performance' => $cashierPerf,
            'shift_performance' => $shiftPerf,
        ];
    }

    private function getCategoriesAnalytics(Carbon $from, Carbon $to, ?string $locationId): array
    {
        // Sales by category - disabled as categories table doesn't exist
        $salesByCategory = collect();

        // Expenses by category
        $expensesByCategory = Expense::query()
            ->join('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->where('expenses.status', 'ok')
            ->whereBetween('expenses.day', [$from->toDateString(), $to->toDateString()])
            ->when($locationId, fn($q) => $q->where('expenses.location_id', $locationId))
            ->selectRaw('expense_categories.name as category, SUM(expenses.amount) as total, COUNT(*) as count')
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderByDesc('total')
            ->limit(15)
            ->get()
            ->map(fn($r) => ['category' => $r->category, 'total' => round($r->total, 2), 'count' => $r->count])
            ->values();

        return [
            'sales_by_category' => $salesByCategory,
            'expenses_by_category' => $expensesByCategory,
        ];
    }

    private function buildMainChart(Carbon $from, Carbon $to, ?string $locationId, int $days): array
    {
        $useDaily = $days <= 62;
        $labels = []; $sales = []; $expenses = []; $invoices = [];

        if ($useDaily) {
            $salesRows = Sale::query()->selectRaw('DATE(at) as d, SUM(total) as total, COUNT(*) as count')
                ->whereBetween('at', [$from, $to])->when($locationId, fn($q) => $q->where('location_id', $locationId))
                ->groupBy('d')->get()->keyBy('d');
            $expenseRows = Expense::query()->selectRaw('DATE(day) as d, SUM(amount) as total')
                ->where('status','ok')->whereBetween('day', [$from->toDateString(), $to->toDateString()])
                ->when($locationId, fn($q) => $q->where('location_id', $locationId))
                ->groupBy('d')->get()->keyBy('d');
            for ($i = 0; $i < $days; $i++) {
                $d = $from->copy()->addDays($i)->toDateString();
                $labels[] = Carbon::parse($d)->format('m/d');
                $sales[] = round((float) ($salesRows[$d]->total ?? 0), 2);
                $expenses[] = round((float) ($expenseRows[$d]->total ?? 0), 2);
                $invoices[] = (int) ($salesRows[$d]->count ?? 0);
            }
        } else {
            $cursor = $from->copy()->startOfMonth();
            $end = $to->copy()->startOfMonth();
            while ($cursor <= $end) {
                $mStart = $cursor->copy()->startOfMonth();
                $mEnd = $cursor->copy()->endOfMonth();
                $labels[] = $mStart->format('Y-m');
                $sales[] = round((float) Sale::query()->whereBetween('at', [$mStart, $mEnd])->when($locationId, fn($q) => $q->where('location_id', $locationId))->sum('total'), 2);
                $expenses[] = round((float) Expense::query()->where('status','ok')->whereBetween('day', [$mStart->toDateString(), $mEnd->toDateString()])->when($locationId, fn($q) => $q->where('location_id', $locationId))->sum('amount'), 2);
                $invoices[] = (int) Sale::query()->whereBetween('at', [$mStart, $mEnd])->when($locationId, fn($q) => $q->where('location_id', $locationId))->count();
                $cursor->addMonth();
            }
        }

        return ['labels'=>$labels,'sales'=>$sales,'expenses'=>$expenses,'invoices'=>$invoices,'mode'=>$useDaily?'daily':'monthly'];
    }
}