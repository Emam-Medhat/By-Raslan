<?php

namespace App\Modules\Reporting\Http\Controllers;

use App\Modules\Accounting\Models\Expense;
use App\Modules\Catalog\Models\Item;
use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Orders\Models\OnlineOrder;
use App\Modules\Organization\Models\Location;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Purchasing\Models\SupplierPayment;
use App\Modules\Production\Models\ProdOrder;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\Shift;
use App\Modules\Transfers\Models\Transfer;
use App\Modules\Trips\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController
{
    public function index(Request $request): View
    {
        $user = $request->user();
        [$range, $from, $to, $prevFrom, $prevTo, $periodLabel] = $this->resolveRange($request);
        $locations = $this->visibleLocations($user);
        $locationIds = $locations->pluck('id')->all();
        $selectedLocation = $this->resolveLocation($request, $user, $locationIds);

        // Date ranges
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();
        $weekStart = $today->copy()->startOfWeek();
        $monthStart = $today->copy()->startOfMonth();
        $yearStart = $today->copy()->startOfYear();

        // Build queries with location scoping
        $salesQuery = Sale::query()->whereBetween('at', [$from, $to]);
        $prevSalesQuery = Sale::query()->whereBetween('at', [$prevFrom, $prevTo]);
        $expensesQuery = Expense::query()->where('status', 'ok')->whereBetween('day', [$from->toDateString(), $to->toDateString()]);
        $prevExpensesQuery = Expense::query()->where('status', 'ok')->whereBetween('day', [$prevFrom->toDateString(), $prevTo->toDateString()]);
        $paymentsQuery = SupplierPayment::query()->whereBetween('created_at', [$from, $to]);
        $prevPaymentsQuery = SupplierPayment::query()->whereBetween('created_at', [$prevFrom, $prevTo]);
        $onlineQuery = OnlineOrder::query()->whereBetween('created_at', [$from, $to]);

        if ($selectedLocation) {
            $salesQuery->where('location_id', $selectedLocation);
            $prevSalesQuery->where('location_id', $selectedLocation);
            $expensesQuery->where('location_id', $selectedLocation);
            $prevExpensesQuery->where('location_id', $selectedLocation);
            $onlineQuery->where('location_id', $selectedLocation);
        }

        $salesTotal = (float) $salesQuery->sum('total');
        $prevSalesTotal = (float) $prevSalesQuery->sum('total');
        $salesCount = (int) $salesQuery->count();
        $prevSalesCount = (int) $prevSalesQuery->count();

        $expensesTotal = (float) $expensesQuery->sum('amount');
        $prevExpensesTotal = (float) $prevExpensesQuery->sum('amount');

        $paymentsTotal = $selectedLocation ? null : (float) $paymentsQuery->sum('amount');
        $prevPaymentsTotal = $selectedLocation ? null : (float) $prevPaymentsQuery->sum('amount');

        $onlineCount = (int) $onlineQuery->count();
        $onlineTotal = (float) $onlineQuery->sum('total');

        // Today's metrics
        $todaySalesQuery = Sale::query()->whereDate('at', $today);
        $yesterdaySalesQuery = Sale::query()->whereDate('at', $yesterday);
        $weekSalesQuery = Sale::query()->whereBetween('at', [$weekStart, $today]);
        $monthSalesQuery = Sale::query()->whereBetween('at', [$monthStart, $today]);
        $yearSalesQuery = Sale::query()->whereBetween('at', [$yearStart, $today]);

        if ($selectedLocation) {
            $todaySalesQuery->where('location_id', $selectedLocation);
            $yesterdaySalesQuery->where('location_id', $selectedLocation);
            $weekSalesQuery->where('location_id', $selectedLocation);
            $monthSalesQuery->where('location_id', $selectedLocation);
            $yearSalesQuery->where('location_id', $selectedLocation);
        }

        $todaySales = (float) $todaySalesQuery->sum('total');
        $todaySalesCount = (int) $todaySalesQuery->count();
        $yesterdaySales = (float) $yesterdaySalesQuery->sum('total');
        $weekSales = (float) $weekSalesQuery->sum('total');
        $monthSales = (float) $monthSalesQuery->sum('total');
        $yearSales = (float) $yearSalesQuery->sum('total');

        $todayExpensesQuery = Expense::query()->where('status', 'ok')->whereDate('day', $today);
        $yesterdayExpensesQuery = Expense::query()->where('status', 'ok')->whereDate('day', $yesterday);
        if ($selectedLocation) {
            $todayExpensesQuery->where('location_id', $selectedLocation);
            $yesterdayExpensesQuery->where('location_id', $selectedLocation);
        }
        $todayExpenses = (float) $todayExpensesQuery->sum('amount');
        $yesterdayExpenses = (float) $yesterdayExpensesQuery->sum('amount');

        $packedToday = (int) Box::query()->where('status', 'packed')->whereDate('packed_at', $today)
            ->when($selectedLocation, fn($q) => $q->where('location_id', $selectedLocation))->count();

        $transfersInTransit = (int) Transfer::query()->where('status', 'transit')
            ->when($selectedLocation, fn($q) => $q->where(function($qq) use ($selectedLocation) {
                $qq->where('from_loc', $selectedLocation)->orWhere('to_loc', $selectedLocation);
            }))->count();

        $openProdOrders = (int) ProdOrder::query()->whereIn('status', ['planned', 'running'])->count();

        // Permissions
        $canViewSales = $user->isOwner() || $user->hasPermission('acc.view') || $user->hasPermission('pos.shift');
        $canViewPack = $user->isOwner() || $user->hasPermission('prod.pack');
        $canViewStock = $user->isOwner() || $user->hasPermission('stock.view');
        $canViewProd = $user->isOwner() || $user->hasPermission('prod.orders');
        $canViewAcc = $user->isOwner() || $user->hasPermission('acc.view');
        $canViewPurchasing = $user->isOwner() || $user->hasPermission('stock.view');

        // Branches and sites
        $branches = $locations->filter(fn($l) => in_array($l->type, ['dark', 'shop']))->values();
        $sites = $locations->filter(fn($l) => in_array($l->type, ['factory', 'warehouse', 'donation']))->values();

        // Branch sales for today
        $branchSales = [];
        if ($canViewSales) {
            $branchSalesData = Sale::query()->selectRaw('location_id, SUM(total) as total, COUNT(*) as count')
                ->whereDate('at', $today)
                ->when($selectedLocation, fn($q) => $q->where('location_id', $selectedLocation))
                ->groupBy('location_id')
                ->get();
            foreach ($branchSalesData as $row) {
                $branchSales[$row->location_id] = ['total' => $row->total, 'count' => $row->count];
            }
        }

        // Top selling items today
        $topItemsToday = Sale::query()
            ->join('sale_lines', 'sales.id', '=', 'sale_lines.sale_id')
            ->join('items', 'sale_lines.item_id', '=', 'items.id')
            ->whereDate('sales.at', $today)
            ->when($selectedLocation, fn($q) => $q->where('sales.location_id', $selectedLocation))
            ->selectRaw('items.id, items.name, SUM(sale_lines.qty) as total_qty, SUM(sale_lines.total) as total_sales')
            ->groupBy('items.id', 'items.name')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();

        // Recent low stock alerts
        $lowStock = $this->lowStockItems($selectedLocation, $locationIds);
        $stockValueMovements = (float) StockMove::query()
            ->when($selectedLocation, fn($q) => $q->where('location_id', $selectedLocation))
            ->where('kind', 'receive')
            ->whereBetween('at', [$from, $to])
            ->selectRaw('COALESCE(SUM(ABS(qty) * COALESCE(unit_cost,0)),0) as v')->value('v');

        $openShifts = Shift::query()->with(['cashierUser:id,name', 'location:id,name'])
            ->where('status', 'open')
            ->when($selectedLocation, fn($q) => $q->where('location_id', $selectedLocation))
            ->when(!$user->isOwner() && !$user->hasPermission('acc.view'), fn($q) => $q->where('cashier', $user->id))
            ->latest('opened_at')->limit(8)->get();

        $recentSales = Sale::query()->with(['cashierUser:id,name', 'location:id,name'])
            ->when($selectedLocation, fn($q) => $q->where('location_id', $selectedLocation))
            ->latest('at')->limit(10)->get();
        $recentOrders = OnlineOrder::query()->with(['customer:id,name', 'location:id,name'])
            ->when($selectedLocation, fn($q) => $q->where('location_id', $selectedLocation))
            ->latest('id')->limit(10)->get();
        $recentExpenses = Expense::query()->with(['category:id,name', 'author:id,name', 'location:id,name'])
            ->where('status', 'ok')
            ->when($selectedLocation, fn($q) => $q->where('location_id', $selectedLocation))
            ->latest('at')->limit(10)->get();
        $recentMoves = StockMove::query()->with(['item:id,name', 'user:id,name', 'location:id,name'])
            ->when($selectedLocation, fn($q) => $q->where('location_id', $selectedLocation))
            ->latest('at')->limit(10)->get();
        $recentTransfers = Transfer::query()->with(['fromLocation:id,name', 'toLocation:id,name'])
            ->when($selectedLocation, fn($q) => $q->where(function ($qq) use ($selectedLocation) {
                $qq->where('from_loc', $selectedLocation)->orWhere('to_loc', $selectedLocation);
            }))
            ->latest('id')->limit(10)->get();

        // Modules for tiles
        $modules = [
            ['id' => 'pos', 'label' => 'الكاشير', 'icon' => 'fa-cash-register', 'color' => '#f5c800', 'href' => route('pos.index'), 'perm' => 'pos.sell'],
            ['id' => 'sales', 'label' => 'المبيعات', 'icon' => 'fa-receipt', 'color' => '#2563EB', 'href' => route('sales.index'), 'perm' => 'acc.view'],
            ['id' => 'stock', 'label' => 'المخزون', 'icon' => 'fa-boxes-stacked', 'color' => '#1a7f37', 'href' => route('stock.index'), 'perm' => 'stock.view'],
            ['id' => 'transfers', 'label' => 'التحويلات', 'icon' => 'fa-right-left', 'color' => '#16a777', 'href' => route('transfers.index'), 'perm' => 'stock.view'],
            ['id' => 'purchasing', 'label' => 'المشتريات', 'icon' => 'fa-bag-shopping', 'color' => '#86562f', 'href' => route('purchasing.index'), 'perm' => 'stock.view'],
            ['id' => 'production', 'label' => 'الإنتاج', 'icon' => 'fa-industry', 'color' => '#7c3aed', 'href' => route('production.index'), 'perm' => 'prod.orders'],
            ['id' => 'pack', 'label' => 'التغليف', 'icon' => 'fa-box-open', 'color' => '#db2777', 'href' => route('pack.index'), 'perm' => 'prod.pack'],
            ['id' => 'trips', 'label' => 'الرحلات', 'icon' => 'fa-truck', 'color' => '#ea580c', 'href' => route('trips.index'), 'perm' => 'trips.load'],
            ['id' => 'accounts', 'label' => 'الحسابات', 'icon' => 'fa-wallet', 'color' => '#1a7f37', 'href' => route('accounts.index'), 'perm' => 'acc.view'],
            ['id' => 'settings', 'label' => 'الإعدادات', 'icon' => 'fa-gear', 'color' => '#6b7280', 'href' => route('settings.index'), 'perm' => 'settings.edit'],
        ];
        $modules = array_filter($modules, fn($m) => !$m['perm'] || $user->isOwner() || $user->hasPermission($m['perm']));

        $boxQuery = Box::query()->where('status', 'stock');
        $boxCount = (int) $boxQuery->when($selectedLocation, fn($q) => $q->where('location_id', $selectedLocation))->count();

        $chart = $this->salesChart($from, $to, $prevFrom, $prevTo, $selectedLocation, $range);
        $branchPerformance = $this->branchPerformance($user, $locations, $from, $to);
        $alerts = $this->alerts($user, $selectedLocation, $locationIds, $lowStock);
        $kpiData = $this->buildKpiData($from, $to, $prevFrom, $prevTo, $selectedLocation, $user, $today, $yesterday, $weekStart, $monthStart, $yearStart);

        return view('dashboard', [
            'kpiData' => $kpiData,
            'chart' => $chart,
            'branchPerformance' => $branchPerformance,
            'lowStockItems' => $lowStock['items'],
            'topItemsToday' => $topItemsToday,
            'recentSales' => $recentSales,
            'recentOrders' => $recentOrders,
            'recentExpenses' => $recentExpenses,
            'recentMoves' => $recentMoves,
            'recentTransfers' => $recentTransfers,
            'openShifts' => $openShifts,
            'alerts' => $alerts,
            'locations' => $locations,
            'selectedLocation' => $selectedLocation,
            'range' => $range,
            'periodLabel' => $periodLabel,
            'from' => $from,
            'to' => $to,
            'generatedAt' => now(),
            'canChooseLocation' => $user->isOwner() || $user->hasPermission('acc.view'),
            'todaySales' => $todaySales,
            'todaySalesCount' => $todaySalesCount,
            'yesterdaySales' => $yesterdaySales,
            'weekSales' => $weekSales,
            'monthSales' => $monthSales,
            'yearSales' => $yearSales,
            'todayExpenses' => $todayExpenses,
            'yesterdayExpenses' => $yesterdayExpenses,
            'packedToday' => $packedToday,
            'transfersInTransit' => $transfersInTransit,
            'openProdOrders' => $openProdOrders,
            'branches' => $branches,
            'sites' => $sites,
            'branchSales' => $branchSales,
            'modules' => $modules,
            'canViewSales' => $canViewSales,
            'canViewPack' => $canViewPack,
            'canViewStock' => $canViewStock,
            'canViewProd' => $canViewProd,
            'canViewAcc' => $canViewAcc,
            'canViewPurchasing' => $canViewPurchasing,
        ]);
    }

    private function resolveRange(Request $request): array
    {
        $range = $request->string('range', '30d')->toString();
        $now = Carbon::now();
        $from = $now->copy()->subDays(29)->startOfDay();
        $to = $now->copy()->endOfDay();
        $label = 'آخر 30 يوماً';

        if ($range === 'today') {
            $from = $now->copy()->startOfDay(); $to = $now->copy()->endOfDay(); $label = 'اليوم';
        } elseif ($range === '7d') {
            $from = $now->copy()->subDays(6)->startOfDay(); $label = 'آخر 7 أيام';
        } elseif ($range === '30d') {
            $from = $now->copy()->subDays(29)->startOfDay(); $label = 'آخر 30 يوماً';
        } elseif ($range === 'month') {
            $from = $now->copy()->startOfMonth(); $to = $now->copy()->endOfMonth(); $label = 'الشهر الحالي';
        } elseif ($range === 'prev_month') {
            $from = $now->copy()->subMonthNoOverflow()->startOfMonth(); $to = $now->copy()->subMonthNoOverflow()->endOfMonth(); $label = 'الشهر السابق';
        } elseif ($range === 'year') {
            $from = $now->copy()->startOfYear(); $to = $now->copy()->endOfYear(); $label = 'السنة الحالية';
        } elseif ($range === 'custom') {
            try {
                $from = Carbon::parse($request->input('from', $from->toDateString()))->startOfDay();
                $to = Carbon::parse($request->input('to', $to->toDateString()))->endOfDay();
                if ($from->gt($to)) [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
                $label = 'فترة مخصصة';
            } catch (\Throwable $e) {
                $range = '30d';
            }
        } else {
            $range = '30d';
        }

        $days = max(1, $from->diffInDays($to) + 1);
        $prevTo = $from->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        return [$range, $from, $to, $prevFrom, $prevTo, $label];
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

    private function resolveLocation(Request $request, $user, array $visibleIds): ?string
    {
        $requested = $request->string('location_id')->toString();
        $canChoose = $user->isOwner() || $user->hasPermission('acc.view');
        if (!$canChoose) return $user->home_location_id ?: null;
        if ($requested === '' || $requested === 'all') return null;
        return in_array($requested, $visibleIds, true) ? $requested : null;
    }

    private function changePercent(float $current, float $previous): ?float
    {
        if (abs($previous) < 0.00001) return $current > 0 ? 100.0 : null;
        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    private function buildKpiData(Carbon $from, Carbon $to, Carbon $prevFrom, Carbon $prevTo, ?string $locationId, $user, Carbon $today, Carbon $yesterday, Carbon $weekStart, Carbon $monthStart, Carbon $yearStart): array
    {
        $canSales = $user->isOwner() || $user->hasPermission('acc.view') || $user->hasPermission('pos.sell');
        $canAcc = $user->isOwner() || $user->hasPermission('acc.view');
        $canStock = $user->isOwner() || $user->hasPermission('stock.view');
        $canUsers = $user->isOwner() || $user->hasPermission('users.manage');
        $canPurchasing = $user->isOwner() || $user->hasPermission('stock.view');
        $canProduction = $user->isOwner() || $user->hasPermission('prod.orders');
        $canTrips = $user->isOwner() || $user->hasPermission('trips.load');
        $canOrders = $user->isOwner() || $user->hasPermission('orders.handle');

        $items = [];

        // Sales KPIs
        if ($canSales) {
            $salesTotal = (float) Sale::query()->whereBetween('at', [$from, $to])->when($locationId, fn($q) => $q->where('location_id', $locationId))->sum('total');
            $prevSalesTotal = (float) Sale::query()->whereBetween('at', [$prevFrom, $prevTo])->when($locationId, fn($q) => $q->where('location_id', $locationId))->sum('total');
            $salesCount = (int) Sale::query()->whereBetween('at', [$from, $to])->when($locationId, fn($q) => $q->where('location_id', $locationId))->count();
            $prevSalesCount = (int) Sale::query()->whereBetween('at', [$prevFrom, $prevTo])->when($locationId, fn($q) => $q->where('location_id', $locationId))->count();

            $todaySales = (float) Sale::query()->whereDate('at', $today)->when($locationId, fn($q) => $q->where('location_id', $locationId))->sum('total');
            $yesterdaySales = (float) Sale::query()->whereDate('at', $yesterday)->when($locationId, fn($q) => $q->where('location_id', $locationId))->sum('total');
            $weekSales = (float) Sale::query()->whereBetween('at', [$weekStart, $today])->when($locationId, fn($q) => $q->where('location_id', $locationId))->sum('total');
            $monthSales = (float) Sale::query()->whereBetween('at', [$monthStart, $today])->when($locationId, fn($q) => $q->where('location_id', $locationId))->sum('total');
            $yearSales = (float) Sale::query()->whereBetween('at', [$yearStart, $today])->when($locationId, fn($q) => $q->where('location_id', $locationId))->sum('total');

            $items[] = $this->kpi('إجمالي المبيعات (الفترة)', $salesTotal, 'ج.م', $this->changePercent($salesTotal, $prevSalesTotal), 'chart-line', 'sales.index', 'currency');
            $items[] = $this->kpi('مبيعات اليوم', $todaySales, 'ج.م', $this->changePercent($todaySales, $yesterdaySales), 'calendar-day', 'sales.index', 'currency');
            $items[] = $this->kpi('مبيعات هذا الأسبوع', $weekSales, 'ج.م', null, 'calendar-week', 'sales.index', 'currency');
            $items[] = $this->kpi('مبيعات هذا الشهر', $monthSales, 'ج.م', null, 'calendar', 'sales.index', 'currency');
            $items[] = $this->kpi('مبيعات هذه السنة', $yearSales, 'ج.م', null, 'calendar-alt', 'sales.index', 'currency');
            $items[] = $this->kpi('عدد الفواتير (الفترة)', $salesCount, 'فاتورة', $this->changePercent($salesCount, $prevSalesCount), 'receipt', 'sales.index', 'number');
            $items[] = $this->kpi('متوسط الفاتورة', $salesCount > 0 ? $salesTotal / $salesCount : 0, 'ج.م', null, 'calculator', 'sales.index', 'currency');
        }

        // Expenses & Net
        if ($canAcc) {
            $expensesTotal = (float) Expense::query()->where('status', 'ok')->whereBetween('day', [$from->toDateString(), $to->toDateString()])->when($locationId, fn($q) => $q->where('location_id', $locationId))->sum('amount');
            $prevExpensesTotal = (float) Expense::query()->where('status', 'ok')->whereBetween('day', [$prevFrom->toDateString(), $prevTo->toDateString()])->when($locationId, fn($q) => $q->where('location_id', $locationId))->sum('amount');
            $todayExpenses = (float) Expense::query()->where('status', 'ok')->whereDate('day', $today)->when($locationId, fn($q) => $q->where('location_id', $locationId))->sum('amount');
            $yesterdayExpenses = (float) Expense::query()->where('status', 'ok')->whereDate('day', $yesterday)->when($locationId, fn($q) => $q->where('location_id', $locationId))->sum('amount');

            $items[] = $this->kpi('إجمالي المصروفات', $expensesTotal, 'ج.م', $this->changePercent($expensesTotal, $prevExpensesTotal), 'money-bill-wave', 'expenses.index', 'currency', true);
            $items[] = $this->kpi('مصروفات اليوم', $todayExpenses, 'ج.م', $this->changePercent($todayExpenses, $yesterdayExpenses), 'calendar-day', 'expenses.index', 'currency', true);

            $salesTotal = (float) Sale::query()->whereBetween('at', [$from, $to])->when($locationId, fn($q) => $q->where('location_id', $locationId))->sum('total');
            $prevSalesTotal = (float) Sale::query()->whereBetween('at', [$prevFrom, $prevTo])->when($locationId, fn($q) => $q->where('location_id', $locationId))->sum('total');
            $net = $salesTotal - $expensesTotal;
            $prevNet = $prevSalesTotal - $prevExpensesTotal;
            $items[] = $this->kpi('صافي المبيعات', $net, 'ج.م', $this->changePercent($net, $prevNet), 'chart-line-up', 'accounts.index', 'currency');

            $paymentsTotal = $locationId ? null : (float) SupplierPayment::query()->whereBetween('created_at', [$from, $to])->sum('amount');
            $prevPaymentsTotal = $locationId ? null : (float) SupplierPayment::query()->whereBetween('created_at', [$prevFrom, $prevTo])->sum('amount');
            if ($paymentsTotal !== null) {
                $items[] = $this->kpi('مدفوعات الموردين', $paymentsTotal, 'ج.م', $this->changePercent($paymentsTotal, $prevPaymentsTotal), 'hand-holding-dollar', 'accounts.suppliers.index', 'currency', true);
            }
        }

        // Inventory KPIs
        if ($canStock) {
            $products = (int) Item::query()->where('active', 1)->count();
            $boxesStock = (int) Box::query()->where('status', 'stock')->when($locationId, fn($q) => $q->where('location_id', $locationId))->count();
            $boxesTransit = (int) Box::query()->where('status', 'transit')->when($locationId, fn($q) => $q->where('location_id', $locationId))->count();
            $lowStock = $this->lowStockItems($locationId, $locationId ? [$locationId] : []);
            $stockValue = (float) StockMove::query()
                ->when($locationId, fn($q) => $q->where('location_id', $locationId))
                ->where('kind', 'receive')
                ->whereBetween('at', [$from, $to])
                ->selectRaw('COALESCE(SUM(ABS(qty) * COALESCE(unit_cost,0)),0) as v')->value('v');

            $items[] = $this->kpi('المنتجات النشطة', $products, 'منتج', null, 'box-open', 'items.index', 'number');
            $items[] = $this->kpi('علب في المخزون', $boxesStock, 'علبة', null, 'boxes-stacked', 'stock.index', 'number');
            $items[] = $this->kpi('علب في التحويل', $boxesTransit, 'علبة', null, 'truck-moving', 'transfers.index', 'number');
            $items[] = $this->kpi('قيمة الاستلامات', $stockValue, 'ج.م', null, 'dollar-sign', 'purchasing.index', 'currency');
            $items[] = $this->kpi('منخفض المخزون', $lowStock['low_count'], 'صنف', null, 'exclamation-triangle', 'stock.index', 'number', $lowStock['low_count'] > 0);
            $items[] = $this->kpi('نافد المخزون', $lowStock['out_count'], 'صنف', null, 'times-circle', 'stock.index', 'number', $lowStock['out_count'] > 0);
        }

        // Purchasing
        if ($canPurchasing) {
            $poCount = (int) PurchaseOrder::query()->whereBetween('created_at', [$from, $to])->count();
            $poTotal = (float) PurchaseOrder::query()
                ->whereBetween('created_at', [$from, $to])
                ->join('po_lines', 'purchase_orders.id', '=', 'po_lines.po_id')
                ->selectRaw('COALESCE(SUM(po_lines.qty * po_lines.price),0) as total')
                ->value('total');
            $suppliers = (int) Supplier::query()->where('active', 1)->count();
            $pendingPO = (int) PurchaseOrder::query()->where('status', 'pending')->count();

            $items[] = $this->kpi('أوامر التوريد', $poCount, 'أمر', null, 'file-contract', 'purchasing.index', 'number');
            $items[] = $this->kpi('قيمة أوامر التوريد', $poTotal, 'ج.م', null, 'dollar-sign', 'purchasing.index', 'currency');
            $items[] = $this->kpi('الموردون النشطون', $suppliers, 'مورد', null, 'truck-field', 'purchasing.suppliers.index', 'number');
            $items[] = $this->kpi('أوامر بانتظار الموافقة', $pendingPO, 'أمر', null, 'clock', 'purchasing.index', 'number', $pendingPO > 0);
        }

        // Production
        if ($canProduction) {
            $openOrders = (int) ProdOrder::query()->whereIn('status', ['planned', 'running'])->count();
            $plannedOrders = (int) ProdOrder::query()->where('status', 'planned')->count();
            $runningOrders = (int) ProdOrder::query()->where('status', 'running')->count();
            $completedToday = (int) ProdOrder::query()->where('status', 'done')->whereDate('due', $today)->count();

            $items[] = $this->kpi('أوامر الإنتاج المفتوحة', $openOrders, 'أمر', null, 'industry', 'production.index', 'number');
            $items[] = $this->kpi('قيد التخطيط', $plannedOrders, 'أمر', null, 'clipboard-list', 'production.index', 'number');
            $items[] = $this->kpi('قيد التنفيذ', $runningOrders, 'أمر', null, 'cogs', 'production.index', 'number');
            $items[] = $this->kpi('مكتملة اليوم', $completedToday, 'أمر', null, 'check-circle', 'production.index', 'number');
        }

        // Packing
        if ($canProduction) {
            $packedToday = (int) Box::query()->where('status', 'packed')->whereDate('packed_at', $today)->when($locationId, fn($q) => $q->where('location_id', $locationId))->count();
            $items[] = $this->kpi('علب مغلفة اليوم', $packedToday, 'علبة', null, 'box-open', 'pack.index', 'number');
        }

        // Trips
        if ($canTrips) {
            $tripsToday = (int) Trip::query()->whereDate('created_at', $today)->count();
            $tripsLoaded = (int) Trip::query()->where('status', 'out')->count();
            $tripsDelivered = (int) Trip::query()->where('status', 'done')->whereDate('left_at', $today)->count();

            $items[] = $this->kpi('الرحلات اليوم', $tripsToday, 'رحلة', null, 'truck', 'trips.index', 'number');
            $items[] = $this->kpi('محمولة', $tripsLoaded, 'رحلة', null, 'truck-loading', 'trips.index', 'number');
            $items[] = $this->kpi('مسلّمة اليوم', $tripsDelivered, 'رحلة', null, 'truck-fast', 'trips.index', 'number');
        }

        // Online Orders
        if ($canOrders) {
            $onlineCount = (int) OnlineOrder::query()->whereBetween('created_at', [$from, $to])->when($locationId, fn($q) => $q->where('location_id', $locationId))->count();
            $onlineTotal = (float) OnlineOrder::query()->whereBetween('created_at', [$from, $to])->when($locationId, fn($q) => $q->where('location_id', $locationId))->sum('total');
            $pendingOnline = (int) OnlineOrder::query()->whereIn('status', ['new', 'accepted', 'preparing'])->when($locationId, fn($q) => $q->where('location_id', $locationId))->count();
            $completedOnline = (int) OnlineOrder::query()->where('status', 'delivered')->whereBetween('created_at', [$from, $to])->when($locationId, fn($q) => $q->where('location_id', $locationId))->count();

            $items[] = $this->kpi('طلبات الأونلاين', $onlineCount, 'طلب', null, 'cart-shopping', 'orders.index', 'number');
            $items[] = $this->kpi('قيمة طلبات الأونلاين', $onlineTotal, 'ج.م', null, 'dollar-sign', 'orders.index', 'currency');
            $items[] = $this->kpi('قيد المعالجة', $pendingOnline, 'طلب', null, 'spinner', 'orders.index', 'number', $pendingOnline > 0);
            $items[] = $this->kpi('مسلّمة', $completedOnline, 'طلب', null, 'check-circle', 'orders.index', 'number');
        }

        // Shifts
        if ($canSales) {
            $openShifts = (int) Shift::query()->where('status', 'open')->when($locationId, fn($q) => $q->where('location_id', $locationId))->count();
            $closedToday = (int) Shift::query()->where('status', 'closed')->whereDate('closed_at', $today)->when($locationId, fn($q) => $q->where('location_id', $locationId))->count();
            $totalShiftSales = (float) Sale::query()
                ->whereDate('at', $today)
                ->when($locationId, fn($q) => $q->where('location_id', $locationId))
                ->sum('total');

            $items[] = $this->kpi('الورديات المفتوحة', $openShifts, 'وردية', null, 'clock', 'shifts.index', 'number');
            $items[] = $this->kpi('مغلقة اليوم', $closedToday, 'وردية', null, 'lock', 'shifts.index', 'number');
            $items[] = $this->kpi('إيرادات الورديات اليوم', $totalShiftSales, 'ج.م', null, 'cash-register', 'shifts.index', 'currency');
        }

        // General
        $items[] = $this->kpi('العملاء', (int) DB::table('customers')->count(), 'عميل', null, 'users', null, 'number');
        if ($canUsers) $items[] = $this->kpi('المستخدمون النشطون', (int) DB::table('users')->where('active', 1)->count(), 'مستخدم', null, 'users-cog', 'users.index', 'number');

        return $items;
    }

    private function kpi(string $label, $value, string $unit, ?float $change, string $icon, ?string $route, string $format = 'number', bool $inverse = false): array
    {
        $route = $route && \Illuminate\Support\Facades\Route::has($route) ? route($route) : null;
        return compact('label','value','unit','change','icon','route','format','inverse');
    }

    private function salesChart(Carbon $from, Carbon $to, Carbon $prevFrom, Carbon $prevTo, ?string $locationId, string $range): array
    {
        $days = max(1, $from->diffInDays($to) + 1);
        $useDaily = $days <= 62;
        $labels = []; $current = []; $previous = [];

        if ($useDaily) {
            $currentRows = Sale::query()->selectRaw('DATE(at) as d, SUM(total) as total')
                ->whereBetween('at', [$from, $to])->when($locationId, fn($q) => $q->where('location_id', $locationId))
                ->groupBy('d')->pluck('total', 'd');
            $prevRows = Sale::query()->selectRaw('DATE(at) as d, SUM(total) as total')
                ->whereBetween('at', [$prevFrom, $prevTo])->when($locationId, fn($q) => $q->where('location_id', $locationId))
                ->groupBy('d')->pluck('total', 'd');
            for ($i = 0; $i < $days; $i++) {
                $d = $from->copy()->addDays($i);
                $p = $prevFrom->copy()->addDays($i);
                $labels[] = $d->format('m/d');
                $current[] = round((float) ($currentRows[$d->toDateString()] ?? 0), 2);
                $previous[] = round((float) ($prevRows[$p->toDateString()] ?? 0), 2);
            }
        } else {
            $cursor = $from->copy()->startOfMonth();
            $end = $to->copy()->startOfMonth();
            while ($cursor <= $end) {
                $mStart = $cursor->copy()->startOfMonth();
                $mEnd = $cursor->copy()->endOfMonth();
                $labels[] = $mStart->format('Y-m');
                $current[] = round((float) Sale::query()->whereBetween('at', [$mStart, $mEnd])->when($locationId, fn($q) => $q->where('location_id', $locationId))->sum('total'), 2);
                $prevStart = $mStart->copy()->subYear();
                $prevEnd = $mEnd->copy()->subYear();
                $previous[] = round((float) Sale::query()->whereBetween('at', [$prevStart, $prevEnd])->when($locationId, fn($q) => $q->where('location_id', $locationId))->sum('total'), 2);
                $cursor->addMonth();
            }
        }

        return ['labels'=>$labels,'current'=>$current,'previous'=>$previous,'mode'=>$useDaily?'daily':'monthly'];
    }

    private function lowStockItems(?string $locationId, array $locationIds): array
    {
        $balances = StockMove::query()
            ->selectRaw('item_id, location_id, SUM(qty) as qty')
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->when(!$locationId && !empty($locationIds), fn($q) => $q->whereIn('location_id', $locationIds))
            ->groupBy('item_id', 'location_id')
            ->get();
        $byItem = [];
        foreach ($balances as $b) {
            $byItem[$b->item_id] = ($byItem[$b->item_id] ?? 0) + (float) $b->qty;
        }
        $items = DB::table('items')->where('active', 1)->get(['id','name','unit']);
        $low = []; $lowCount = 0; $outCount = 0;
        foreach ($items as $item) {
            $qty = round((float) ($byItem[$item->id] ?? 0), 3);
            if ($qty <= 0) { $outCount++; $low[] = ['id'=>$item->id,'name'=>$item->name,'unit'=>$item->unit,'qty'=>$qty,'status'=>'out']; }
            elseif ($qty <= 5) { $lowCount++; $low[] = ['id'=>$item->id,'name'=>$item->name,'unit'=>$item->unit,'qty'=>$qty,'status'=>'low']; }
        }
        usort($low, fn($a,$b) => $a['qty'] <=> $b['qty']);
        return ['items'=>array_slice($low,0,8), 'low_count'=>$lowCount, 'out_count'=>$outCount];
    }

    private function branchPerformance($user, $locations, Carbon $from, Carbon $to): array
    {
        if (!($user->isOwner() || $user->hasPermission('acc.view'))) return [];
        $rows = [];
        foreach ($locations as $loc) {
            $sales = (float) Sale::query()->where('location_id', $loc->id)->whereBetween('at', [$from, $to])->sum('total');
            $count = (int) Sale::query()->where('location_id', $loc->id)->whereBetween('at', [$from, $to])->count();
            $expenses = (float) Expense::query()->where('location_id', $loc->id)->where('status','ok')->whereBetween('day', [$from->toDateString(), $to->toDateString()])->sum('amount');
            $openShift = Shift::query()->where('location_id', $loc->id)->where('status','open')->exists();
            $boxes = (int) Box::query()->where('location_id', $loc->id)->where('status','stock')->count();
            $rows[] = [
                'id'=>$loc->id,'name'=>$loc->name,'type'=>$loc->type,'active'=>$loc->active,'city'=>$loc->city,
                'sales'=>$sales,'count'=>$count,'expenses'=>$expenses,'open_shift'=>$openShift,'boxes'=>$boxes,
            ];
        }
        usort($rows, fn($a,$b) => $b['sales'] <=> $a['sales']);
        return $rows;
    }

    private function alerts($user, ?string $locationId, array $locationIds, array $lowStock): array
    {
        $alerts = [];
        foreach (array_slice($lowStock['items'], 0, 5) as $item) {
            $alerts[] = [
                'type' => $item['status'] === 'out' ? 'danger' : 'warning',
                'title' => $item['status'] === 'out' ? 'منتج نافد من المخزون' : 'مخزون منخفض',
                'body' => $item['name'].' — الكمية الحالية '.number_format($item['qty'], 2).' '.$item['unit'],
                'route' => route('stock.index'),
                'action' => 'عرض المخزون',
            ];
        }
        $openShifts = Shift::query()->with('location:id,name','cashierUser:id,name')->where('status','open')
            ->when($locationId, fn($q)=>$q->where('location_id',$locationId))->limit(3)->get();
        foreach ($openShifts as $shift) {
            $hours = $shift->opened_at ? $shift->opened_at->diffInHours(now()) : 0;
            if ($hours >= 12) {
                $alerts[] = [
                    'type'=>'warning','title'=>'وردية مفتوحة منذ وقت طويل',
                    'body'=>($shift->location?->name ?? $shift->location_id).' — '.$shift->cashierUser?->name.' — '.$hours.' ساعة',
                    'route'=>route('shifts.index'),'action'=>'مراجعة الورديات',
                ];
            }
        }
        $pendingOrders = OnlineOrder::query()->whereIn('status', ['new','accepted','preparing'])
            ->when($locationId, fn($q)=>$q->where('location_id',$locationId))->count();
        if ($pendingOrders > 0 && ($user->isOwner() || $user->hasPermission('orders.handle'))) {
            $alerts[] = ['type'=>'info','title'=>'طلبات أونلاين قيد المعالجة','body'=>$pendingOrders.' طلب يحتاج متابعة','route'=>route('orders.index'),'action'=>'عرض الطلبات'];
        }
        $pendingExpenses = Expense::query()->where('status','pending')
            ->when($locationId, fn($q)=>$q->where('location_id',$locationId))->count();
        if ($pendingExpenses > 0 && ($user->isOwner() || $user->hasPermission('acc.approve'))) {
            $alerts[] = ['type'=>'warning','title'=>'مصروفات بانتظار الاعتماد','body'=>$pendingExpenses.' مصروف يحتاج قرار','route'=>route('expenses.index'),'action'=>'مراجعة المصروفات'];
        }
        $pendingPO = \App\Modules\Purchasing\Models\PurchaseOrder::query()->where('status','pending')->count();
        if ($pendingPO > 0 && ($user->isOwner() || $user->hasPermission('stock.view'))) {
            $alerts[] = ['type'=>'info','title'=>'أوامر توريد بانتظار الموافقة','body'=>$pendingPO.' أمر يحتاج مراجعة','route'=>route('purchasing.index'),'action'=>'عرض أوامر التوريد'];
        }
        $openTransfers = Transfer::query()->where('status','transit')
            ->when($locationId, fn($q)=>$q->where(function($qq) use ($locationId) {
                $qq->where('from_loc',$locationId)->orWhere('to_loc',$locationId);
            }))->count();
        if ($openTransfers > 0 && ($user->isOwner() || $user->hasPermission('stock.view'))) {
            $alerts[] = ['type'=>'info','title'=>'تحويلات في الطريق','body'=>$openTransfers.' تحويل يحتاج متابعة','route'=>route('transfers.index'),'action'=>'عرض التحويلات'];
        }
        return $alerts;
    }
}