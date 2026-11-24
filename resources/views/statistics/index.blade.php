@extends('layouts.app')

@section('title', 'مركز التحليلات')
@section('page-title', 'مركز التحليلات')

@section('content')
@php
    $fmtMoney = fn($v) => number_format((float)$v, 2);
    $fmtNum = fn($v) => number_format((float)$v, 0);
    $fmtPct = fn($v) => $v !== null ? ($v >= 0 ? '+' . number_format($v, 1) : number_format($v, 1)) . '%' : '—';
    $pctClass = fn($v) => $v === null ? '' : ($v >= 0 ? 'text-accent-700' : 'text-red-600');
    $changeIcon = fn($v) => $v === null ? 'minus' : ($v >= 0 ? 'trending-up' : 'trending-down');
    $changePercent = fn($curr, $prev) => $prev && $prev > 0 ? round((($curr - $prev) / $prev) * 100, 1) : null;
@endphp

<div class="space-y-6">
    {{-- Global Filters Bar --}}
    <div class="ds-card">
        <div class="ds-card-header flex flex-col md:flex-row md:items-end md:justify-between gap-4">
            <div>
                <h2 class="ds-card-title">تحليلات شاملة للنظام</h2>
                <p class="text-sm text-slate-500">جميع الوحدات — بيانات حقيقية محدثة لحظياً</p>
            </div>
            <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 items-end w-full">
                <div>
                    <label class="ds-label">الفترة</label>
                    <select name="range" class="ds-select" onchange="this.form.submit()">
                        <option value="today" @selected($range==='today')>اليوم</option>
                        <option value="7d" @selected($range==='7d')>آخر 7 أيام</option>
                        <option value="30d" @selected($range==='30d')>آخر 30 يوماً</option>
                        <option value="90d" @selected($range==='90d')>آخر 90 يوماً</option>
                        <option value="month" @selected($range==='month')>الشهر الحالي</option>
                        <option value="prev_month" @selected($range==='prev_month')>الشهر السابق</option>
                        <option value="year" @selected($range==='year')>السنة الحالية</option>
                        <option value="custom" @selected($range==='custom')>مخصص</option>
                    </select>
                </div>
                @if($range === 'custom')
                    <div><label class="ds-label">من</label><input type="date" name="from" value="{{ $from->toDateString() }}" class="ds-input"></div>
                    <div><label class="ds-label">إلى</label><input type="date" name="to" value="{{ $to->toDateString() }}" class="ds-input"></div>
                @endif
                @if($canChooseLocation)
                    <div>
                        <label class="ds-label">الفرع</label>
                        <select name="location_id" class="ds-select" onchange="this.form.submit()">
                            <option value="all">جميع الفروع</option>
                            @forelse($locations as $loc)
                                <option value="{{ $loc->id }}" @selected($locationId == $loc->id)>{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label class="ds-label">التصنيف</label>
                    <select name="category_id" class="ds-select" onchange="this.form.submit()">
                        <option value="">جميع التصنيفات</option>
                        @forelse($categories as $cat)
                            <option value="{{ $cat->id }}" @selected($categoryId == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2 xl:col-span-3 flex flex-wrap gap-2">
                    <button type="submit" class="ds-btn ds-btn-primary">تطبيق</button>
                    <a href="{{ route('statistics.index') }}" class="ds-btn ds-btn-secondary">إعادة ضبط</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Main Chart --}}
    <div class="ds-card">
        <div class="ds-card-header flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <h2 class="ds-card-title">نظرة عامة: المبيعات vs المصروفات vs الفواتير</h2>
            <span class="ds-badge ds-badge-accent">{{ $periodLabel }} {{ $locationId ? '· ' . $locations->firstWhere('id', $locationId)?->name : '· جميع الفروع' }}</span>
        </div>
        <div class="ds-card-body">
            <div class="h-[380px]"><canvas id="mainChart"></canvas></div>
        </div>
    </div>

    {{-- Module Analytics Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        {{-- SALES --}}
        <div class="ds-card xl:col-span-1">
            <div class="ds-card-header">
                <h2 class="ds-card-title flex items-center gap-2"><x-icon name="receipt-text" size="16" class="text-brand-600" /> المبيعات</h2>
            </div>
            <div class="ds-card-body space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="ds-kpi"><div class="ds-kpi-label">إجمالي المبيعات</div><div class="ds-kpi-value num">{{ $fmtMoney($salesAnalytics['total']) }} <span class="ds-kpi-meta">ج.م</span></div><div class="ds-kpi-meta {{ $pctClass($salesAnalytics['change']) }}"><x-icon :name="$changeIcon($salesAnalytics['change'])" size="14" /> {{ $fmtPct($salesAnalytics['change']) }}</div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">عدد الفواتير</div><div class="ds-kpi-value num">{{ $fmtNum($salesAnalytics['count']) }} <span class="ds-kpi-meta">فاتورة</span></div><div class="ds-kpi-meta {{ $pctClass($changePercent($salesAnalytics['count'], $salesAnalytics['prev_count'])) }}"><x-icon :name="$changeIcon($changePercent($salesAnalytics['count'], $salesAnalytics['prev_count']))" size="14" /></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">متوسط الفاتورة</div><div class="ds-kpi-value num">{{ $fmtMoney($salesAnalytics['avg_ticket']) }} <span class="ds-kpi-meta">ج.م</span></div><div class="ds-kpi-meta {{ $pctClass($salesAnalytics['avg_ticket_change']) }}"><x-icon :name="$changeIcon($salesAnalytics['avg_ticket_change'])" size="14" /></div></div>
                </div>

                <div class="h-[200px]"><canvas id="salesChart"></canvas></div>

                <h3 class="font-semibold text-slate-800">أكثر الأصناف مبيعاً</h3>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @forelse($salesAnalytics['top_items'] as $item)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate">{{ $item['name'] }}</div>
                                <div class="text-xs text-slate-500">{{ $fmtNum($item['orders']) }} طلب · {{ $fmtNum($item['qty']) }} وحدة</div>
                            </div>
                            <div class="text-right ml-3"><div class="font-bold">{{ $fmtMoney($item['sales']) }}</div></div>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 py-4">لا توجد بيانات</div>
                    @endforelse
                </div>

                <h3 class="font-semibold text-slate-800 mt-4">طرق الدفع</h3>
                <div class="space-y-2">
                    @forelse($salesAnalytics['payment_methods'] as $pm)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <span>{{ $pm['method'] }}</span>
                            <div class="text-right"><div class="font-semibold">{{ $fmtMoney($pm['total']) }}</div><div class="text-xs text-slate-500">{{ $fmtNum($pm['count']) }} عملية</div></div>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 py-4">لا توجد بيانات</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- EXPENSES --}}
        <div class="ds-card xl:col-span-1">
            <div class="ds-card-header">
                <h2 class="ds-card-title flex items-center gap-2"><x-icon name="banknote" size="16" class="text-red-600" /> المصروفات</h2>
            </div>
            <div class="ds-card-body space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="ds-kpi"><div class="ds-kpi-label">إجمالي المصروفات</div><div class="ds-kpi-value num">{{ $fmtMoney($expensesAnalytics['total']) }} <span class="ds-kpi-meta">ج.م</span></div><div class="ds-kpi-meta {{ $pctClass($expensesAnalytics['change']) }}"><x-icon :name="$changeIcon($expensesAnalytics['change'])" size="14" /> {{ $fmtPct($expensesAnalytics['change']) }}</div></div>
                </div>

                <div class="h-[200px]"><canvas id="expensesChart"></canvas></div>

                <h3 class="font-semibold text-slate-800">بالتقسيم</h3>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @forelse($expensesAnalytics['by_category'] as $cat)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate">{{ $cat['category'] }}</div>
                                <div class="text-xs text-slate-500">{{ $fmtNum($cat['count']) }} مصروف</div>
                            </div>
                            <div class="text-right ml-3">
                                <div class="font-bold">{{ $fmtMoney($cat['total']) }}</div>
                                <div class="text-xs text-slate-500">{{ $cat['pct'] }}%</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 py-4">لا توجد بيانات</div>
                    @endforelse
                </div>

                <h3 class="font-semibold text-slate-800 mt-4">أهم الموردين</h3>
                <div class="space-y-2 max-h-40 overflow-y-auto">
                    @forelse($expensesAnalytics['top_vendors'] as $vendor)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <span class="truncate">{{ $vendor['vendor'] }}</span>
                            <div class="text-right ml-3"><div class="font-semibold">{{ $fmtMoney($vendor['total']) }}</div><div class="text-xs text-slate-500">{{ $fmtNum($vendor['count']) }} مصروف</div></div>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 py-4">لا توجد بيانات</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- PURCHASING --}}
        <div class="ds-card xl:col-span-1">
            <div class="ds-card-header">
                <h2 class="ds-card-title flex items-center gap-2"><x-icon name="shopping-bag" size="16" class="text-amber-700" /> المشتريات</h2>
            </div>
            <div class="ds-card-body space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="ds-kpi"><div class="ds-kpi-label">قيمة أوامر التوريد</div><div class="ds-kpi-value num">{{ $fmtMoney($purchasingAnalytics['po_total']) }} <span class="ds-kpi-meta">ج.م</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">عدد الأوامر</div><div class="ds-kpi-value num">{{ $fmtNum($purchasingAnalytics['po_count']) }} <span class="ds-kpi-meta">أمر</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">مدفوعات الموردين</div><div class="ds-kpi-value num">{{ $fmtMoney($purchasingAnalytics['payments_total']) }} <span class="ds-kpi-meta">ج.م</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">نسبة السداد</div><div class="ds-kpi-value num">{{ $fmtNum($purchasingAnalytics['payment_ratio']) }} <span class="ds-kpi-meta">%</span></div></div>
                </div>

                <h3 class="font-semibold text-slate-800">حسب الحالة</h3>
                <div class="space-y-2">
                    @forelse($purchasingAnalytics['by_status'] as $status)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <span class="capitalize">{{ $status['status'] }}</span>
                            <div class="text-right ml-3"><div class="font-semibold">{{ $fmtMoney($status['total']) }}</div><div class="text-xs text-slate-500">{{ $fmtNum($status['count']) }} أمر</div></div>
                        </div>
                    @endforeach
                </div>

                <h3 class="font-semibold text-slate-800 mt-4">أهم الموردين</h3>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @forelse($purchasingAnalytics['top_suppliers'] as $sup)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <div class="truncate">{{ $sup['name'] }}</div>
                            <div class="text-right ml-3"><div class="font-semibold">{{ $fmtMoney($sup['total']) }}</div><div class="text-xs text-slate-500">{{ $fmtNum($sup['orders']) }} أمر</div></div>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 py-4">لا توجد بيانات</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- INVENTORY --}}
        <div class="ds-card xl:col-span-1">
            <div class="ds-card-header">
                <h2 class="ds-card-title flex items-center gap-2"><x-icon name="boxes" size="16" class="text-green-700" /> المخزون</h2>
            </div>
            <div class="ds-card-body space-y-4">
                <div class="grid grid-cols-3 gap-2">
                    <div class="ds-kpi"><div class="ds-kpi-label">قيمة الاستلامات</div><div class="ds-kpi-value num">{{ $fmtMoney($inventoryAnalytics['stock_value']) }} <span class="ds-kpi-meta">ج.م</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">علب بالمخزون</div><div class="ds-kpi-value num">{{ $fmtNum($inventoryAnalytics['boxes_stock']) }} <span class="ds-kpi-meta">علبة</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">بالطريق</div><div class="ds-kpi-value num">{{ $fmtNum($inventoryAnalytics['boxes_transit']) }} <span class="ds-kpi-meta">علبة</span></div></div>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div class="ds-kpi {{ $inventoryAnalytics['out_count'] > 0 ? 'hl' : '' }}"><div class="ds-kpi-label">نافد</div><div class="ds-kpi-value num">{{ $fmtNum($inventoryAnalytics['out_count']) }} <span class="ds-kpi-meta">صنف</span></div></div>
                    <div class="ds-kpi {{ $inventoryAnalytics['low_count'] > 0 ? 'hl' : '' }}"><div class="ds-kpi-label">منخفض</div><div class="ds-kpi-value num">{{ $fmtNum($inventoryAnalytics['low_count']) }} <span class="ds-kpi-meta">صنف</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">مُنتَج</div><div class="ds-kpi-value num">{{ $fmtNum($inventoryAnalytics['boxes_produced']) }} <span class="ds-kpi-meta">علبة</span></div></div>
                </div>

                <h3 class="font-semibold text-slate-800">الحركات حسب النوع</h3>
                <div class="space-y-2 max-h-40 overflow-y-auto">
                    @forelse($inventoryAnalytics['by_type'] as $type)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <span class="capitalize">{{ $type['type'] }}</span>
                            <div class="text-right ml-3"><div class="font-semibold">{{ $fmtNum($type['qty']) }}</div><div class="text-xs text-slate-500">{{ $fmtNum($type['moves']) }} حركة</div></div>
                        </div>
                    @endforeach
                </div>

                <h3 class="font-semibold text-slate-800 mt-4">الأكثر حركة</h3>
                <div class="space-y-2 max-h-40 overflow-y-auto">
                    @forelse($inventoryAnalytics['top_items'] as $item)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <span class="truncate">{{ $item['name'] }}</span>
                            <div class="text-right ml-3"><div class="font-semibold">{{ $fmtNum($item['qty']) }}</div><div class="text-xs text-slate-500">{{ $fmtNum($item['moves']) }} حركة</div></div>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 py-4">لا توجد بيانات</div>
                    @endforelse
                </div>

                @if(!empty($inventoryAnalytics['out_of_stock']))
                <h3 class="font-semibold text-slate-800 mt-4 text-red-600">⚠ نافد المخزون</h3>
                <div class="space-y-2 max-h-32 overflow-y-auto">
                    @forelse($inventoryAnalytics['out_of_stock'] as $item)
                        <div class="flex items-center justify-between py-1 text-sm">
                            <span>{{ $item['name'] }}</span>
                            <span class="text-red-600 font-semibold">{{ $fmtNum($item['qty']) }} {{ $item['unit'] }}</span>
                        </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- ONLINE ORDERS --}}
        <div class="ds-card xl:col-span-1">
            <div class="ds-card-header">
                <h2 class="ds-card-title flex items-center gap-2"><x-icon name="shopping-cart" size="16" class="text-purple-700" /> طلبات الأونلاين</h2>
            </div>
            <div class="ds-card-body space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="ds-kpi"><div class="ds-kpi-label">إجمالي الطلبات</div><div class="ds-kpi-value num">{{ $fmtNum($onlineAnalytics['count']) }} <span class="ds-kpi-meta">طلب</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">القيمة الإجمالية</div><div class="ds-kpi-value num">{{ $fmtMoney($onlineAnalytics['total']) }} <span class="ds-kpi-meta">ج.م</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">متوسط الطلب</div><div class="ds-kpi-value num">{{ $fmtMoney($onlineAnalytics['avg_order']) }} <span class="ds-kpi-meta">ج.م</span></div></div>
                </div>

                <div class="h-[200px]"><canvas id="onlineChart"></canvas></div>

                <h3 class="font-semibold text-slate-800">حسب الحالة</h3>
                <div class="space-y-2">
                    @forelse($onlineAnalytics['by_status'] as $status)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <span class="capitalize">{{ $status['status'] }}</span>
                            <div class="text-right ml-3"><div class="font-semibold">{{ $fmtMoney($status['total']) }}</div><div class="text-xs text-slate-500">{{ $fmtNum($status['count']) }} طلب</div></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- PRODUCTION --}}
        <div class="ds-card xl:col-span-1">
            <div class="ds-card-header">
                <h2 class="ds-card-title flex items-center gap-2"><x-icon name="factory" size="16" class="text-violet-700" /> الإنتاج</h2>
            </div>
            <div class="ds-card-body space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="ds-kpi"><div class="ds-kpi-label">إجمالي الأوامر</div><div class="ds-kpi-value num">{{ $fmtNum($productionAnalytics['total']) }} <span class="ds-kpi-meta">أمر</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">مكتملة</div><div class="ds-kpi-value num">{{ $fmtNum($productionAnalytics['completed']) }} <span class="ds-kpi-meta">أمر</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">قيد التنفيذ</div><div class="ds-kpi-value num">{{ $fmtNum($productionAnalytics['running']) }} <span class="ds-kpi-meta">أمر</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">نسبة الإنجاز</div><div class="ds-kpi-value num">{{ $fmtNum($productionAnalytics['completion_rate']) }} <span class="ds-kpi-meta">%</span></div></div>
                </div>

                <div class="h-[200px]"><canvas id="productionChart"></canvas></div>

                <h3 class="font-semibold text-slate-800">أكثر المنتجات تصنيعاً</h3>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @forelse($productionAnalytics['by_product'] as $prod)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <span class="truncate">{{ $prod['name'] }}</span>
                            <div class="text-right ml-3"><div class="font-semibold">{{ $fmtNum($prod['qty']) }}</div><div class="text-xs text-slate-500">{{ $fmtNum($prod['orders']) }} أمر</div></div>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 py-4">لا توجد بيانات</div>
                    @endforelse
                </div>
            </div>
        </div

        {{-- SHIFTS --}}
        <div class="ds-card xl:col-span-1">
            <div class="ds-card-header">
                <h2 class="ds-card-title flex items-center gap-2"><x-icon name="clock" size="16" class="text-blue-700" /> الورديات</h2>
            </div>
            <div class="ds-card-body space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="ds-kpi"><div class="ds-kpi-label">إجمالي الورديات</div><div class="ds-kpi-value num">{{ $fmtNum($shiftsAnalytics['total_shifts']) }} <span class="ds-kpi-meta">وردية</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">مغلقة</div><div class="ds-kpi-value num">{{ $fmtNum($shiftsAnalytics['closed_shifts']) }} <span class="ds-kpi-meta">وردية</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">مفتوحة</div><div class="ds-kpi-value num">{{ $fmtNum($shiftsAnalytics['open_shifts']) }} <span class="ds-kpi-meta">وردية</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">إيرادات الورديات</div><div class="ds-kpi-value num">{{ $fmtMoney($shiftsAnalytics['total_sales']) }} <span class="ds-kpi-meta">ج.م</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">مصروفات الورديات</div><div class="ds-kpi-value num">{{ $fmtMoney($shiftsAnalytics['total_expenses']) }} <span class="ds-kpi-meta">ج.م</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">متوسط/وردية</div><div class="ds-kpi-value num">{{ $fmtMoney($shiftsAnalytics['avg_shift_sales']) }} <span class="ds-kpi-meta">ج.م</span></div></div>
                </div>

                <h3 class="font-semibold text-slate-800">أداء الكاشيرات</h3>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @forelse($shiftsAnalytics['by_cashier'] as $cashier)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <div>
                                <div class="font-medium">{{ $cashier['name'] }}</div>
                                <div class="text-xs text-slate-500">{{ $fmtNum($cashier['shifts']) }} وردية</div>
                            </div>
                            <div class="text-right ml-3">
                                <div class="font-semibold">{{ $fmtMoney($cashier['sales']) }}</div>
                                <div class="text-xs text-slate-500">مصروفات: {{ $fmtMoney($cashier['expenses']) }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 py-4">لا توجد بيانات</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- TRANSFERS --}}
        <div class="ds-card xl:col-span-1">
            <div class="ds-card-header">
                <h2 class="ds-card-title flex items-center gap-2"><x-icon name="arrow-left-right" size="16" class="text-cyan-700" /> التحويلات</h2>
            </div>
            <div class="ds-card-body space-y-4">
                <div class="grid grid-cols-3 gap-2">
                    <div class="ds-kpi"><div class="ds-kpi-label">إجمالي</div><div class="ds-kpi-value num">{{ $fmtNum($transfersAnalytics['total']) }} <span class="ds-kpi-meta">تحويل</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">قيد الانتظار</div><div class="ds-kpi-value num">{{ $fmtNum($transfersAnalytics['pending']) }} <span class="ds-kpi-meta">تحويل</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">في الطريق</div><div class="ds-kpi-value num">{{ $fmtNum($transfersAnalytics['transit']) }} <span class="ds-kpi-meta">تحويل</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">مستلمة</div><div class="ds-kpi-value num">{{ $fmtNum($transfersAnalytics['received']) }} <span class="ds-kpi-meta">تحويل</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">ملغية</div><div class="ds-kpi-value num">{{ $fmtNum($transfersAnalytics['cancelled']) }} <span class="ds-kpi-meta">تحويل</span></div></div>
                </div>

                <h3 class="font-semibold text-slate-800">أهم الفروع المرسلة</h3>
                <div class="space-y-2 max-h-40 overflow-y-auto">
                    @forelse($transfersAnalytics['from_locations'] as $loc)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <span>{{ $loc['name'] }}</span>
                            <span class="font-semibold">{{ $fmtNum($loc['count']) }}</span>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 py-4">لا توجد بيانات</div>
                    @endforelse
                </div>

                <h3 class="font-semibold text-slate-800 mt-4">أهم الفروع المستقبلة</h3>
                <div class="space-y-2 max-h-40 overflow-y-auto">
                    @forelse($transfersAnalytics['to_locations'] as $loc)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <span>{{ $loc['name'] }}</span>
                            <span class="font-semibold">{{ $fmtNum($loc['count']) }}</span>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 py-4">لا توجد بيانات</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- CUSTOMERS --}}
        <div class="ds-card xl:col-span-1">
            <div class="ds-card-header">
                <h2 class="ds-card-title flex items-center gap-2"><x-icon name="users" size="16" class="text-indigo-700" /> العملاء</h2>
            </div>
            <div class="ds-card-body space-y-4">
                <div class="grid grid-cols-3 gap-2">
                    <div class="ds-kpi"><div class="ds-kpi-label">إجمالي العملاء</div><div class="ds-kpi-value num">{{ $fmtNum($customersAnalytics['total']) }} <span class="ds-kpi-meta">عميل</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">نشطون بالفترة</div><div class="ds-kpi-value num">{{ $fmtNum($customersAnalytics['active']) }} <span class="ds-kpi-meta">عميل</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">جدد</div><div class="ds-kpi-value num">{{ $fmtNum($customersAnalytics['new']) }} <span class="ds-kpi-meta">عميل</span></div></div>
                </div>

                <h3 class="font-semibold text-slate-800">أهم العملاء</h3>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @forelse($customersAnalytics['top_customers'] as $cust)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <span class="truncate">{{ $cust['name'] }}</span>
                            <div class="text-right ml-3"><div class="font-semibold">{{ $fmtMoney($cust['sales']) }}</div><div class="text-xs text-slate-500">{{ $fmtNum($cust['orders']) }} طلب</div></div>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 py-4">لا توجد بيانات</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- SUPPLIERS --}}
        <div class="ds-card xl:col-span-1">
            <div class="ds-card-header">
                <h2 class="ds-card-title flex items-center gap-2"><x-icon name="truck" size="16" class="text-orange-700" /> الموردين</h2>
            </div>
            <div class="ds-card-body space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="ds-kpi"><div class="ds-kpi-label">إجمالي الموردين</div><div class="ds-kpi-value num">{{ $fmtNum($suppliersAnalytics['total']) }} <span class="ds-kpi-meta">مورد</span></div></div>
                    <div class="ds-kpi"><div class="ds-kpi-label">نشطون بالفترة</div><div class="ds-kpi-value num">{{ $fmtNum($suppliersAnalytics['active']) }} <span class="ds-kpi-meta">مورد</span></div></div>
                </div>

                <h3 class="font-semibold text-slate-800">أهم الموردين (قيمة أوامر التوريد)</h3>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @forelse($suppliersAnalytics['top_suppliers'] as $sup)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate">{{ $sup['name'] }}</div>
                                <div class="text-xs text-slate-500">{{ $fmtNum($sup['orders']) }} أمر</div>
                            </div>
                            <div class="text-right ml-3">
                                <div class="font-semibold">{{ $fmtMoney($sup['total_po']) }}</div>
                                <div class="text-xs {{ $sup['balance'] > 0 ? 'text-red-600' : 'text-accent-700' }}">متبقي: {{ $fmtMoney($sup['balance']) }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 py-4">لا توجد بيانات</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- BRANCHES --}}
        @if(!empty($branchesAnalytics))
        <div class="ds-card xl:col-span-1">
            <div class="ds-card-header">
                <h2 class="ds-card-title flex items-center gap-2"><x-icon name="building" size="16" class="text-teal-700" /> أداء الفروع</h2>
            </div>
            <div class="ds-card-body p-0">
                <div class="ds-table-wrap">
                    <table class="ds-table">
                        <thead>
                            <tr><th>الفرع</th><th class="num">المبيعات</th><th class="num">الفواتير</th><th class="num">المصروفات</th><th class="num">الصافي</th><th class="num">أونلاين</th><th class="num">مشتريات</th></tr>
                        </thead>
                        <tbody>
                            @forelse($branchesAnalytics as $branch)
                                <tr>
                                    <td>
                                        <div class="font-semibold">{{ $branch['name'] }}</div>
                                        <div class="text-xs text-slate-500">{{ $branch['type'] }} {{ $branch['city'] ? '· ' . $branch['city'] : '' }}</div>
                                    </td>
                                    <td class="num font-semibold">{{ $fmtMoney($branch['sales']) }}</td>
                                    <td class="num">{{ $fmtNum($branch['sales_count']) }}</td>
                                    <td class="num">{{ $fmtMoney($branch['expenses']) }}</td>
                                    <td class="num font-semibold {{ $branch['net'] < 0 ? 'text-red-600' : 'text-accent-700' }}">{{ $fmtMoney($branch['net']) }}</td>
                                    <td class="num">{{ $fmtMoney($branch['online']) }}<br><span class="text-xs text-slate-500">{{ $fmtNum($branch['online_count']) }} طلب</span></td>
                                    <td class="num">{{ $fmtMoney($branch['purchasing']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- USERS --}}
        <div class="ds-card xl:col-span-1">
            <div class="ds-card-header">
                <h2 class="ds-card-title flex items-center gap-2"><x-icon name="users-cog" size="16" class="text-pink-700" /> المستخدمين</h2>
            </div>
            <div class="ds-card-body space-y-4">
                <div class="ds-kpi"><div class="ds-kpi-label">إجمالي المستخدمين النشطين</div><div class="ds-kpi-value num">{{ $fmtNum($usersAnalytics['total_users']) }} <span class="ds-kpi-meta">مستخدم</span></div></div>

                <h3 class="font-semibold text-slate-800">أداء الكاشيرات (مبيعات)</h3>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @forelse($usersAnalytics['cashier_performance'] as $user)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <span class="truncate">{{ $user['name'] }}</span>
                            <div class="text-right ml-3"><div class="font-semibold">{{ $fmtMoney($user['sales']) }}</div><div class="text-xs text-slate-500">{{ $fmtNum($user['invoices']) }} فاتورة</div></div>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 py-4">لا توجد بيانات</div>
                    @endforelse
                </div>

                <h3 class="font-semibold text-slate-800 mt-4">أداء الورديات</h3>
                <div class="space-y-2 max-h-40 overflow-y-auto">
                    @forelse($usersAnalytics['shift_performance'] as $user)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <div>
                                <div class="font-medium">{{ $user['name'] }}</div>
                                <div class="text-xs text-slate-500">{{ $fmtNum($user['shifts']) }} وردية</div>
                            </div>
                            <div class="text-right ml-3">
                                <div class="font-semibold">{{ $fmtMoney($user['sales']) }}</div>
                                <div class="text-xs text-slate-500">مصروفات: {{ $fmtMoney($user['expenses']) }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 py-4">لا توجد بيانات</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- CATEGORIES --}}
        <div class="ds-card xl:col-span-1">
            <div class="ds-card-header">
                <h2 class="ds-card-title flex items-center gap-2"><x-icon name="tags" size="16" class="text-lime-700" /> التصنيفات</h2>
            </div>
            <div class="ds-card-body space-y-4">
                <h3 class="font-semibold text-slate-800">المبيعات حسب التصنيف</h3>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @forelse($categoriesAnalytics['sales_by_category'] as $cat)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate">{{ $cat['category'] }}</div>
                                <div class="text-xs text-slate-500">{{ $fmtNum($cat['qty']) }} وحدة · {{ $fmtNum($cat['lines']) }} سطر</div>
                            </div>
                            <div class="text-right ml-3"><div class="font-semibold">{{ $fmtMoney($cat['sales']) }}</div></div>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 py-4">لا توجد بيانات</div>
                    @endforelse
                </div>

                <h3 class="font-semibold text-slate-800 mt-4">المصروفات حسب التصنيف</h3>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @forelse($categoriesAnalytics['expenses_by_category'] as $cat)
                        <div class="flex items-center justify-between py-2 border-b border-slate-100">
                            <div>
                                <div class="font-medium">{{ $cat['category'] }}</div>
                                <div class="text-xs text-slate-500">{{ $fmtNum($cat['count']) }} مصروف</div>
                            </div>
                            <div class="text-right ml-3"><div class="font-semibold">{{ $fmtMoney($cat['total']) }}</div></div>
                        </div>
                    @empty
                        <div class="text-center text-slate-500 py-4">لا توجد بيانات</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const ctx = document.getElementById('mainChart');
    if (ctx && window.Chart) {
        new Chart(ctx, {
            data: {
                labels: @json($mainChart['labels']),
                datasets: [
                    { type:'bar', label:'المبيعات', data:@json($mainChart['sales']), backgroundColor:'rgba(21,157,145,.75)', borderRadius:6, maxBarThickness:28 },
                    { type:'bar', label:'المصروفات', data:@json($mainChart['expenses']), backgroundColor:'rgba(23,50,77,.20)', borderRadius:6, maxBarThickness:28 },
                    { type:'line', label:'الفواتير', data:@json($mainChart['invoices']), borderColor:'#17324d', backgroundColor:'#17324d', yAxisID:'y1', tension:.35, pointRadius:2 }
                ]
            },
            options: {
                responsive:true, maintainAspectRatio:false, interaction:{mode:'index',intersect:false},
                plugins:{ legend:{position:'bottom', labels:{usePointStyle:true, font:{family:'Cairo'}}}, tooltip:{rtl:true, titleFont:{family:'Cairo'}, bodyFont:{family:'Cairo'}} },
                scales:{
                    x:{grid:{display:false}, ticks:{font:{family:'Cairo',size:11}, color:'#64748b', maxRotation:0}},
                    y:{beginAtZero:true, grid:{color:'rgba(148,163,184,.12)'}, ticks:{font:{family:'Cairo',size:11}, color:'#64748b'}},
                    y1:{beginAtZero:true, position:'right', grid:{drawOnChartArea:false}, ticks:{font:{family:'Cairo',size:11}, color:'#64748b'}}
                }
            }
        });
    }

    // Sales chart
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx && window.Chart && @json($salesAnalytics['chart_labels'])) {
        new Chart(salesCtx, {
            data: {
                labels: @json($salesAnalytics['chart_labels']),
                datasets: [
                    { type:'bar', label:'المبيعات', data:@json($salesAnalytics['chart_sales']), backgroundColor:'rgba(21,157,145,.75)', borderRadius:4, maxBarThickness:20 },
                    { type:'line', label:'الفواتير', data:@json($salesAnalytics['chart_invoices']), borderColor:'#17324d', backgroundColor:'#17324d', yAxisID:'y1', tension:.3, pointRadius:2, borderWidth:2 }
                ]
            },
            options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom', labels:{usePointStyle:true, font:{family:'Cairo'}}}, tooltip:{rtl:true, titleFont:{family:'Cairo'}, bodyFont:{family:'Cairo'}}}, scales:{x:{grid:{display:false}, ticks:{font:{family:'Cairo',size:10}, color:'#64748b'}}, y:{beginAtZero:true, grid:{color:'rgba(148,163,184,.12)'}, ticks:{font:{family:'Cairo',size:10}}}, y1:{beginAtZero:true, position:'right', grid:{drawOnChartArea:false}, ticks:{font:{family:'Cairo',size:10}}}} }
        });
    }

    // Expenses chart
    const expCtx = document.getElementById('expensesChart');
    if (expCtx && window.Chart && @json($expensesAnalytics['chart_labels'])) {
        new Chart(expCtx, {
            data: {
                labels: @json($expensesAnalytics['chart_labels']),
                datasets: [
                    { type:'bar', label:'المصروفات', data:@json($expensesAnalytics['chart_expenses']), backgroundColor:'rgba(198,40,40,.75)', borderRadius:4, maxBarThickness:20 }
                ]
            },
            options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}, tooltip:{rtl:true, titleFont:{family:'Cairo'}, bodyFont:{family:'Cairo'}}}, scales:{x:{grid:{display:false}, ticks:{font:{family:'Cairo',size:10}, color:'#64748b'}}, y:{beginAtZero:true, grid:{color:'rgba(148,163,184,.12)'}, ticks:{font:{family:'Cairo',size:10}}}} }
        });
    }

    // Online orders chart
    const onlineCtx = document.getElementById('onlineChart');
    if (onlineCtx && window.Chart && @json($onlineAnalytics['chart_labels'])) {
        new Chart(onlineCtx, {
            data: {
                labels: @json($onlineAnalytics['chart_labels']),
                datasets: [
                    { type:'bar', label:'الطلبات', data:@json($onlineAnalytics['chart_orders']), backgroundColor:'rgba(139,92,246,.75)', borderRadius:4, maxBarThickness:20 },
                    { type:'line', label:'الإيرادات', data:@json($onlineAnalytics['chart_revenue']), borderColor:'#7c3aed', backgroundColor:'#7c3aed', yAxisID:'y1', tension:.3, pointRadius:2, borderWidth:2 }
                ]
            },
            options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom', labels:{usePointStyle:true, font:{family:'Cairo'}}}, tooltip:{rtl:true, titleFont:{family:'Cairo'}, bodyFont:{family:'Cairo'}}}, scales:{x:{grid:{display:false}, ticks:{font:{family:'Cairo',size:10}, color:'#64748b'}}, y:{beginAtZero:true, grid:{color:'rgba(148,163,184,.12)'}, ticks:{font:{family:'Cairo',size:10}}}, y1:{beginAtZero:true, position:'right', grid:{drawOnChartArea:false}, ticks:{font:{family:'Cairo',size:10}}}} }
        });
    }

    // Production chart
    const prodCtx = document.getElementById('productionChart');
    if (prodCtx && window.Chart && @json($productionAnalytics['chart_labels'])) {
        new Chart(prodCtx, {
            data: {
                labels: @json($productionAnalytics['chart_labels']),
                datasets: [
                    { type:'bar', label:'مكتملة', data:@json($productionAnalytics['chart_completed']), backgroundColor:'rgba(16,185,129,.75)', borderRadius:4, maxBarThickness:20 }
                ]
            },
            options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}, tooltip:{rtl:true, titleFont:{family:'Cairo'}, bodyFont:{family:'Cairo'}}}, scales:{x:{grid:{display:false}, ticks:{font:{family:'Cairo',size:10}, color:'#64748b'}}, y:{beginAtZero:true, grid:{color:'rgba(148,163,184,.12)'}, ticks:{font:{family:'Cairo',size:10}}}} }
        });
    }
})();
</script>
@endpush