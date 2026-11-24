@extends('layouts.app')

@section('title', 'لوحة التحكم')
@section('page-title', 'لوحة التحكم')

@section('content')
@php
    $fmtMoney = fn($v) => number_format((float)$v, 2);
    $fmtNum = fn($v) => number_format((float)$v, 0);
    $fmtPct = fn($v) => $v !== null ? ($v >= 0 ? '+' . number_format($v, 1) : number_format($v, 1)) . '%' : '—';
    $pctClass = fn($v) => $v === null ? '' : ($v >= 0 ? 'text-accent-700' : 'text-red-600');
@endphp

<div class="space-y-6">
    {{-- Top bar: greeting + period + location + actions --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between mb-5">
        <div>
            <h1>أهلاً، {{ auth()->user()?->name }}</h1>
            <p class="text-sm text-slate-500">{{ $periodLabel }} {{ $selectedLocation ? '· الفرع: ' . $locations->firstWhere('id', $selectedLocation)?->name : '· جميع الفروع' }}</p>
        </div>
        <div class="flex items-center gap-3">
            <form method="GET" class="flex items-center gap-2">
                <select name="range" class="ds-select" onchange="this.form.submit()" style="width:auto;">
                    <option value="today" @selected($range==='today')>اليوم</option>
                    <option value="7d" @selected($range==='7d')>آخر 7 أيام</option>
                    <option value="30d" @selected($range==='30d')>آخر 30 يوماً</option>
                    <option value="month" @selected($range==='month')>الشهر الحالي</option>
                    <option value="prev_month" @selected($range==='prev_month')>الشهر السابق</option>
                    <option value="year" @selected($range==='year')>السنة الحالية</option>
                    <option value="custom" @selected($range==='custom')>مخصص</option>
                </select>
                @if($range === 'custom')
                    <input type="date" name="from" value="{{ $from->toDateString() }}" class="ds-input" style="width:140px;">
                    <input type="date" name="to" value="{{ $to->toDateString() }}" class="ds-input" style="width:140px;">
                @endif
                @if($canChooseLocation)
                    <select name="location_id" class="ds-select" onchange="this.form.submit()" style="width:auto;">
                        <option value="all">جميع الفروع</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" @selected($selectedLocation == $loc->id)>{{ $loc->name }}</option>
                        @endforeach
                    </select>
                @endif
            </form>
            <a href="{{ route('statistics.index') }}" class="ds-btn ds-btn-primary">التحليلات الكاملة</a>
        </div>
    </div>

    {{-- KPI Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
        @foreach($kpiData as $kpi)
            @php
                $changeColor = $kpi['change'] !== null ? ($kpi['change'] >= 0 ? 'text-emerald-600' : 'text-red-500') : 'text-slate-400';
                $changeIcon = $kpi['change'] !== null ? ($kpi['change'] >= 0 ? 'trending-up' : 'trending-down') : 'minus';
                $valueDisplay = $kpi['format'] === 'currency' ? $fmtMoney($kpi['value']) : $fmtNum($kpi['value']);
                $kpiIconMap = ['fa-chart-line'=>'chart-line','fa-receipt'=>'receipt-text','fa-wallet'=>'wallet','fa-box'=>'package','fa-boxes-stacked'=>'boxes','fa-arrow-trend-up'=>'trending-up','fa-arrow-trend-down'=>'trending-down','fa-money-bill'=>'banknote','fa-coins'=>'banknote','fa-bag-shopping'=>'shopping-bag','fa-cart-shopping'=>'shopping-cart','fa-users'=>'users','fa-truck'=>'truck','fa-industry'=>'factory','fa-clock'=>'clock','fa-triangle-exclamation'=>'alert-triangle'];
                $kpiIcon = $kpiIconMap[$kpi['icon'] ?? ''] ?? str_replace('fa-','', $kpi['icon'] ?? 'chart-line');
            @endphp
            <a href="{{ $kpi['route'] ?? '#' }}" class="ds-kpi {{ $kpi['inverse'] ? 'hl' : '' }}" {{ !$kpi['route'] ? 'tabindex="-1" aria-disabled="true"' : '' }}>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="ds-kpi-label">{{ $kpi['label'] }}</div>
                        <div class="ds-kpi-value"><span class="num">{{ $valueDisplay }}</span> <span class="text-xs font-semibold text-slate-400">{{ $kpi['unit'] }}</span></div>
                        @if($kpi['change'] !== null)
                            <div class="ds-kpi-meta {{ $changeColor }} flex items-center gap-1">
                                <x-icon :name="$changeIcon" size="14" />
                                <span>{{ $fmtPct($kpi['change']) }} مقارنة بالفترة السابقة</span>
                            </div>
                        @endif
                    </div>
                    <span class="ds-kpi-icon shrink-0 {{ $kpi['inverse'] ? '!bg-white/10 !text-accent-300' : '' }}">
                        <x-icon :name="$kpiIcon" size="20" />
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    {{-- Main Chart + Branch Performance --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 ds-card">
            <div class="ds-card-header">
                <h2 class="ds-card-title">المبيعات — {{ $periodLabel }}</h2>
                <span class="ds-badge ds-badge-accent">{{ $chart['mode'] === 'daily' ? 'يومي' : 'شهري' }}</span>
            </div>
            <div class="ds-card-body">
                <div class="h-[320px]"><canvas id="mainChart"></canvas></div>
            </div>
        </div>

        <div class="ds-card">
            <div class="ds-card-header">
                <h2 class="ds-card-title">أداء الفروع</h2>
            </div>
            <div class="ds-card-body p-0">
                @if(empty($branchPerformance))
                    <div class="ds-empty p-6">لا توجد بيانات للفروع</div>
                @else
                    <div class="ds-table-wrap">
                        <table class="ds-table">
                            <thead>
                                <tr>
                                    <th>الفرع</th>
                                    <th class="num">المبيعات</th>
                                    <th class="num">الفواتير</th>
                                    <th class="num">المصروفات</th>
                                    <th class="num">الصافي</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($branchPerformance as $branch)
                                    <tr>
                                        <td>
                                            <div class="font-semibold">{{ $branch['name'] }}</div>
                                            <div class="text-xs text-slate-500">{{ $branch['type'] }} {{ $branch['city'] ? '· ' . $branch['city'] : '' }}</div>
                                        </td>
                                        <td class="num font-semibold">{{ $fmtMoney($branch['sales']) }}</td>
                                        <td class="num">{{ $fmtNum($branch['count']) }}</td>
                                        <td class="num">{{ $fmtMoney($branch['expenses']) }}</td>
                                        <td class="num font-semibold {{ ($branch['sales'] - $branch['expenses']) < 0 ? 'text-red-600' : 'text-accent-700' }}">
                                            {{ $fmtMoney($branch['sales'] - $branch['expenses']) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Alerts + Quick Actions --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 ds-card">
            <div class="ds-card-header">
                <h2 class="ds-card-title">التنبيهات</h2>
            </div>
            <div class="ds-card-body">
                @forelse($alerts as $alert)
                    <div class="ds-alert {{ $alert['type'] === 'danger' ? 'ds-alert-danger' : ($alert['type'] === 'warning' ? 'ds-alert-warning' : 'ds-alert-info') }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold">{{ $alert['title'] }}</div>
                                <div class="text-sm text-slate-600 mt-1">{{ $alert['body'] }}</div>
                            </div>
                            @if(!empty($alert['route']))
                                <a href="{{ $alert['route'] }}" class="ds-btn ds-btn-sm ds-btn-primary">{{ $alert['action'] ?? 'عرض' }}</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="ds-empty">لا توجد تنبيهات حالية</div>
                @endforelse
            </div>
        </div>

        <div class="ds-card">
            <div class="ds-card-header">
                <h2 class="ds-card-title">إجراءات سريعة</h2>
            </div>
            <div class="ds-card-body space-y-2">
                @can('pos.sell')
                    <a href="{{ route('pos.index') }}" class="ds-btn ds-btn-primary w-full justify-center"><x-icon name="monitor" size="16" /> فتح الكاشير</a>
                @endcan
                @can('stock.view')
                    <a href="{{ route('stock.index') }}" class="ds-btn ds-btn-secondary w-full justify-center"><x-icon name="boxes" size="16" /> المخزون</a>
                @endcan
                @can('acc.view')
                    <a href="{{ route('sales.index') }}" class="ds-btn ds-btn-secondary w-full justify-center"><x-icon name="receipt-text" size="16" /> المبيعات</a>
                @endcan
                @can('stock.view')
                    <a href="{{ route('transfers.index') }}" class="ds-btn ds-btn-secondary w-full justify-center"><x-icon name="arrow-left-right" size="16" /> التحويلات</a>
                @endcan
                @can('prod.orders')
                    <a href="{{ route('production.index') }}" class="ds-btn ds-btn-secondary w-full justify-center"><x-icon name="factory" size="16" /> الإنتاج</a>
                @endcan
                @can('prod.pack')
                    <a href="{{ route('pack.index') }}" class="ds-btn ds-btn-secondary w-full justify-center"><x-icon name="package-open" size="16" /> محطة التغليف</a>
                @endcan
                @can('trips.load')
                    <a href="{{ route('trips.index') }}" class="ds-btn ds-btn-secondary w-full justify-center"><x-icon name="truck" size="16" /> الرحلات</a>
                @endcan
                @can('acc.view')
                    <a href="{{ route('accounts.index') }}" class="ds-btn ds-btn-secondary w-full justify-center"><x-icon name="wallet" size="16" /> الحسابات</a>
                @endcan
            </div>
        </div>
    </div>

    {{-- Top Items Today + Branch Sales Today --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="ds-card">
            <div class="ds-card-header">
                <h2 class="ds-card-title">الأكثر مبيعاً اليوم</h2>
            </div>
            <div class="ds-card-body p-0">
                @if($topItemsToday->isEmpty())
                    <div class="ds-empty p-6">لا توجد مبيعات اليوم</div>
                @else
                    <div class="ds-table-wrap">
                        <table class="ds-table">
                            <thead><tr><th>الصنف</th><th class="num">الكمية</th><th class="num">القيمة</th></tr></thead>
                            <tbody>
                                @foreach($topItemsToday as $item)
                                    <tr>
                                        <td class="font-semibold">{{ $item->name }}</td>
                                        <td class="num">{{ number_format($item->total_qty, 2) }}</td>
                                        <td class="num font-semibold">{{ $fmtMoney($item->total_sales) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="ds-card">
            <div class="ds-card-header">
                <h2 class="ds-card-title">مبيعات الفروع اليوم</h2>
            </div>
            <div class="ds-card-body p-0">
                @if(empty($branchSales))
                    <div class="ds-empty p-6">لا توجد بيانات</div>
                @else
                    <div class="ds-table-wrap">
                        <table class="ds-table">
                            <thead><tr><th>الفرع</th><th class="num">المبيعات</th><th class="num">الفواتير</th></tr></thead>
                            <tbody>
                                @foreach($branchSales as $locId => $data)
                                    <tr>
                                        <td class="font-semibold">{{ $locations->firstWhere('id', $locId)?->name ?? '—' }}</td>
                                        <td class="num font-semibold">{{ $fmtMoney($data['total']) }}</td>
                                        <td class="num">{{ $fmtNum($data['count']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Recent Activity Tables --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="ds-card">
            <div class="ds-card-header">
                <h2 class="ds-card-title">أحدث المبيعات</h2>
                <a href="{{ route('sales.index') }}" class="ds-link text-sm">عرض الكل</a>
            </div>
            <div class="ds-card-body p-0">
                <div class="ds-table-wrap">
                    <table class="ds-table">
                        <thead><tr><th>الفاتورة</th><th>الكاشير</th><th>الفرع</th><th class="num">الإجمالي</th><th class="num">التاريخ</th></tr></thead>
                        <tbody>
                            @forelse($recentSales as $sale)
                                <tr>
                                    <td class="font-semibold">#{{ $sale->no }}</td>
                                    <td>{{ $sale->cashierUser?->name ?? '—' }}</td>
                                    <td>{{ $sale->location?->name ?? '—' }}</td>
                                    <td class="num font-semibold">{{ $fmtMoney($sale->total) }}</td>
                                    <td class="num text-sm">{{ optional($sale->at)->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="ds-empty">لا توجد مبيعات</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="ds-card">
            <div class="ds-card-header">
                <h2 class="ds-card-title">أحدث الطلبات</h2>
                <a href="{{ route('orders.index') }}" class="ds-link text-sm">عرض الكل</a>
            </div>
            <div class="ds-card-body p-0">
                <div class="ds-table-wrap">
                    <table class="ds-table">
                        <thead><tr><th>الطلب</th><th>العميل</th><th>الحالة</th><th class="num">الإجمالي</th></tr></thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                                <tr>
                                    <td class="font-semibold">#{{ $order->no }}</td>
                                    <td>{{ $order->customer?->name ?? '—' }}</td>
                                    <td><span class="ds-badge ds-badge-neutral">{{ $order->status }}</span></td>
                                    <td class="num font-semibold">{{ $fmtMoney($order->total ?? $order->est_total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="ds-empty">لا توجد طلبات</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="ds-card">
            <div class="ds-card-header">
                <h2 class="ds-card-title">أحدث المصروفات</h2>
                <a href="{{ route('expenses.index') }}" class="ds-link text-sm">عرض الكل</a>
            </div>
            <div class="ds-card-body p-0">
                <div class="ds-table-wrap">
                    <table class="ds-table">
                        <thead><tr><th>التصنيف</th><th>البائع</th><th>بواسطة</th><th class="num">المبلغ</th><th class="num">التاريخ</th></tr></thead>
                        <tbody>
                            @forelse($recentExpenses as $exp)
                                <tr>
                                    <td class="font-semibold">{{ $exp->category?->name ?? '—' }}</td>
                                    <td>{{ $exp->vendor ?? '—' }}</td>
                                    <td>{{ $exp->author?->name ?? '—' }}</td>
                                    <td class="num font-semibold">{{ $fmtMoney($exp->amount) }}</td>
                                    <td class="num text-sm">{{ optional($exp->at)->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="ds-empty">لا توجد مصروفات</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="ds-card">
            <div class="ds-card-header">
                <h2 class="ds-card-title">أحدث حركات المخزون</h2>
                <a href="{{ route('stock.index') }}" class="ds-link text-sm">عرض الكل</a>
            </div>
            <div class="ds-card-body p-0">
                <div class="ds-table-wrap">
                    <table class="ds-table">
                        <thead><tr><th>الصنف</th><th>النوع</th><th class="num">الكمية</th><th>الموقع</th><th class="num">التاريخ</th></tr></thead>
                        <tbody>
                            @forelse($recentMoves as $move)
                                <tr>
                                    <td class="font-semibold">{{ $move->item?->name ?? '—' }}</td>
                                    <td><span class="ds-badge ds-badge-neutral">{{ $move->kind }}</span></td>
                                    <td class="num font-semibold">{{ number_format((float)$move->qty, 3) }}</td>
                                    <td>{{ $move->location?->name ?? $move->location_id }}</td>
                                    <td class="num text-sm">{{ optional($move->at)->format('Y-m-d H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="ds-empty">لا توجد حركات</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="ds-card">
            <div class="ds-card-header">
                <h2 class="ds-card-title">الورديات المفتوحة</h2>
                <a href="{{ route('shifts.index') }}" class="ds-link text-sm">عرض الكل</a>
            </div>
            <div class="ds-card-body p-0">
                <div class="ds-table-wrap">
                    <table class="ds-table">
                        <thead><tr><th>الموظف</th><th>الفرع</th><th class="num">فتحت في</th><th class="num">رصيد افتتاحي</th></tr></thead>
                        <tbody>
                            @forelse($openShifts as $shift)
                                <tr>
                                    <td class="font-semibold">{{ $shift->cashierUser?->name ?? '—' }}</td>
                                    <td>{{ $shift->location?->name ?? $shift->location_id }}</td>
                                    <td class="num text-sm">{{ optional($shift->opened_at)->format('Y-m-d H:i') }}</td>
                                    <td class="num">{{ $fmtMoney($shift->opening_cash) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="ds-empty">لا توجد ورديات مفتوحة</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="ds-card">
            <div class="ds-card-header">
                <h2 class="ds-card-title">أحدث التحويلات</h2>
                <a href="{{ route('transfers.index') }}" class="ds-link text-sm">عرض الكل</a>
            </div>
            <div class="ds-card-body p-0">
                <div class="ds-table-wrap">
                    <table class="ds-table">
                        <thead><tr><th>الرقم</th><th>من</th><th>إلى</th><th>الحالة</th><th class="num">التاريخ</th></tr></thead>
                        <tbody>
                            @forelse($recentTransfers as $tr)
                                <tr>
                                    <td class="font-semibold">#{{ $tr->no }}</td>
                                    <td>{{ $tr->fromLocation?->name ?? $tr->from_loc }}</td>
                                    <td>{{ $tr->toLocation?->name ?? $tr->to_loc }}</td>
                                    <td>
                                        <span class="ds-badge {{ $tr->status === 'received' ? 'ds-badge-success' : ($tr->status === 'transit' ? 'ds-badge-warning' : 'ds-badge-neutral') }}">
                                            {{ $tr->status }}
                                        </span>
                                    </td>
                                    <td class="num text-sm">{{ optional($tr->created_at)->format('Y-m-d') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="ds-empty">لا توجد تحويلات</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Low Stock Alert --}}
    @if(!empty($lowStockItems))
    <div class="ds-card border-red-200">
        <div class="ds-card-header bg-red-50 border-red-200">
            <h2 class="ds-card-title text-red-800 flex items-center gap-2"><x-icon name="alert-triangle" size="16" /> تنبيهات المخزون المنخفض</h2>
        </div>
        <div class="ds-card-body p-0">
            <div class="ds-table-wrap">
                <table class="ds-table">
                    <thead><tr><th>الصنف</th><th class="num">الكمية الحالية</th><th>الوحدة</th><th>الحالة</th></tr></thead>
                    <tbody>
                        @foreach($lowStockItems as $item)
                            <tr>
                                <td class="font-semibold">{{ $item['name'] }}</td>
                                <td class="num font-semibold">{{ number_format($item['qty'], 2) }}</td>
                                <td>{{ $item['unit'] }}</td>
                                <td>
                                    <span class="ds-badge {{ $item['status'] === 'out' ? 'ds-badge-danger' : 'ds-badge-warning' }}">
                                        {{ $item['status'] === 'out' ? 'نافد' : 'منخفض' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(() => {
    const ctx = document.getElementById('mainChart');
    if (!ctx || !window.Chart) return;
    new Chart(ctx, {
        data: {
            labels: @json($chart['labels']),
            datasets: [
                {
                    type: 'bar',
                    label: 'المبيعات',
                    data: @json($chart['current']),
                    backgroundColor: 'rgba(21,157,145,.75)',
                    borderRadius: 6,
                    maxBarThickness: 28,
                },
                {
                    type: 'bar',
                    label: 'المصروفات',
                    data: @json(array_fill(0, count($chart['current']), 0)),
                    backgroundColor: 'rgba(23,50,77,.20)',
                    borderRadius: 6,
                    maxBarThickness: 28,
                },
                {
                    type: 'line',
                    label: 'الفترة السابقة',
                    data: @json($chart['previous']),
                    borderColor: '#17324d',
                    backgroundColor: '#17324d',
                    borderWidth: 2,
                    borderDash: [6, 4],
                    fill: false,
                    tension: 0.35,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, font: { family: 'Cairo' } } },
                tooltip: { rtl: true, titleFont: { family: 'Cairo' }, bodyFont: { family: 'Cairo' } }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { family: 'Cairo', size: 11 }, color: '#64748b', maxRotation: 0 } },
                y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,.12)' }, ticks: { font: { family: 'Cairo', size: 11 }, color: '#64748b' } }
            }
        }
    });
})();
</script>
@endpush