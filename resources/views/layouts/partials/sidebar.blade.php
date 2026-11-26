@php
    $user = auth()->user();
    $can = fn(string $perm) => $user && ($user->isOwner() || $user->hasPermission($perm));

    $sidebarModules = [
        [
            'key' => 'home',
            'label' => 'الرئيسية',
            'items' => [
                ['label' => 'لوحة التحكم', 'route' => 'dashboard', 'icon' => 'layout-dashboard', 'match' => 'dashboard*', 'show' => true],
                ['label' => 'الإحصائيات', 'route' => 'statistics.index', 'icon' => 'chart-no-axes-combined', 'match' => 'statistics*', 'show' => $can('acc.view')],
            ],
        ],
        [
            'key' => 'sales',
            'label' => 'المبيعات',
            'items' => [
                ['label' => 'الكاشير', 'route' => 'pos.index', 'icon' => 'monitor', 'match' => 'pos*', 'show' => $can('pos.sell')],
                ['label' => 'المبيعات', 'route' => 'sales.index', 'icon' => 'receipt-text', 'match' => 'sales*', 'show' => $can('acc.view')],
                ['label' => 'المرتجعات', 'route' => 'returns.index', 'icon' => 'undo-2', 'match' => 'returns*', 'show' => $can('acc.view')],
                ['label' => 'طلبات الأونلاين', 'route' => 'orders.index', 'icon' => 'shopping-cart', 'match' => 'orders*', 'show' => $can('orders.handle')],
                ['label' => 'إنشاء طلب (خدمة عملاء)', 'route' => 'orders.create', 'icon' => 'phone-call', 'match' => 'orders/create', 'show' => $can('orders.handle')],
                ['label' => 'الحجوزات', 'route' => 'reservations.index', 'icon' => 'calendar-clock', 'match' => 'reservations*', 'show' => $can('orders.handle')],
                ['label' => 'الطيارين', 'route' => 'drivers.index', 'icon' => 'bike', 'match' => 'drivers*', 'show' => $can('orders.handle')],
                ['label' => 'الأسعار', 'route' => 'prices.index', 'icon' => 'badge-dollar-sign', 'match' => 'prices*', 'show' => $can('prices.edit')],
            ],
        ],
        [
            'key' => 'inventory',
            'label' => 'المخزون',
            'items' => [
                ['label' => 'الأصناف', 'route' => 'items.index', 'icon' => 'package-search', 'match' => 'items*', 'show' => $can('stock.view')],
                ['label' => 'المخزون والحركات', 'route' => 'stock.index', 'icon' => 'boxes', 'match' => 'stock/adjust*', 'show' => $can('stock.adjust') || $can('stock.view')],
                ['label' => 'التحويلات', 'route' => 'transfers.index', 'icon' => 'arrow-left-right', 'match' => 'transfers*', 'show' => $can('stock.view')],
                ['label' => 'الجرد', 'route' => 'stock-counts.index', 'icon' => 'clipboard-check', 'match' => 'stock-counts*', 'show' => $can('stock.view')],
                ['label' => 'تتبّع العلبة', 'route' => 'boxes.index', 'icon' => 'qr-code', 'match' => 'boxes*', 'show' => $can('stock.view')],
                ['label' => 'هالك وتلف', 'route' => 'stock.dispose', 'icon' => 'trash-2', 'match' => 'stock/dispose*', 'show' => $can('stock.adjust')],
            ],
        ],
        [
            'key' => 'purchasing',
            'label' => 'المشتريات',
            'items' => [
                ['label' => 'أوامر التوريد', 'route' => 'purchasing.index', 'icon' => 'shopping-bag', 'match' => 'purchasing$', 'show' => $can('stock.view')],
                ['label' => 'الموردين', 'route' => 'purchasing.suppliers.index', 'icon' => 'truck', 'match' => 'purchasing/suppliers*', 'show' => $can('stock.view')],
                ['label' => 'مرتجعات الموردين', 'route' => 'purchase-returns.index', 'icon' => 'undo-2', 'match' => 'purchase-returns*', 'show' => $can('acc.view')],
                ['label' => 'المواصفات', 'route' => 'purchasing.specs.index', 'icon' => 'file-text', 'match' => 'purchasing/specs*', 'show' => $can('stock.view')],
                ['label' => 'الاستثناءات', 'route' => 'overrides.index', 'icon' => 'shield-check', 'match' => 'overrides*', 'show' => $can('stock.view')],
            ],
        ],
        [
            'key' => 'production',
            'label' => 'الإنتاج',
            'items' => [
                ['label' => 'أوامر الإنتاج', 'route' => 'production.index', 'icon' => 'factory', 'match' => 'production', 'show' => $can('stock.view')],
                ['label' => 'مسارات الإنتاج', 'route' => 'production.routes', 'icon' => 'git-branch', 'match' => 'production/routes*', 'show' => $can('items.edit')],
                ['label' => 'شاشات الإنتاج', 'route' => 'production.station', 'icon' => 'monitor', 'match' => 'production/station*', 'show' => $can('prod.orders')],
                ['label' => 'محطة التغليف', 'route' => 'pack.index', 'icon' => 'package-open', 'match' => 'pack*', 'show' => $can('prod.pack')],
                ['label' => 'تقرير المصنع (حجوزات الغد)', 'route' => 'reports.factory-reservations', 'icon' => 'factory', 'match' => 'reports/factory-reservations*', 'show' => $can('orders.handle')],
                ['label' => 'تحميل العربيات', 'route' => 'trips.index', 'icon' => 'truck', 'match' => 'trips*', 'show' => $can('trips.load')],
            ],
        ],
        [
            'key' => 'accounting',
            'label' => 'الحسابات',
            'items' => [
                ['label' => 'الحسابات العامة', 'route' => 'accounts.index', 'icon' => 'landmark', 'match' => 'accounts', 'show' => $can('acc.view')],
                ['label' => 'الصناديق والتحويلات', 'route' => 'cash-transfers.index', 'icon' => 'wallet', 'match' => 'cash-transfers*', 'show' => $can('acc.view')],
                ['label' => 'حسابات الموردين', 'route' => 'accounts.suppliers.index', 'icon' => 'receipt', 'match' => 'accounts/suppliers*', 'show' => $can('acc.view')],
                ['label' => 'المصروفات', 'route' => 'expenses.index', 'icon' => 'banknote', 'match' => 'expenses*', 'show' => $can('acc.view')],
                ['label' => 'الورديات', 'route' => 'shifts.index', 'icon' => 'clock', 'match' => 'shifts*', 'show' => $can('acc.view')],
                ['label' => 'إقفال اليومية', 'route' => 'reports.daily-closing', 'icon' => 'calendar-check', 'match' => 'reports/daily-closing*', 'show' => $can('acc.view')],
                ['label' => 'مصروفات الورديات', 'route' => 'shift-expenses.index', 'icon' => 'receipt-text', 'match' => 'shift-expenses*', 'show' => $can('acc.view')],
            ],
        ],
        [
            'key' => 'admin',
            'label' => 'الإدارة',
            'items' => [
                ['label' => 'المواقع والفروع', 'route' => 'locations.index', 'icon' => 'store', 'match' => 'locations*', 'show' => $can('stock.view')],
                ['label' => 'المستخدمون', 'route' => 'users.index', 'icon' => 'user-round-cog', 'match' => 'users*', 'show' => $can('users.manage')],
                ['label' => 'الإشعارات', 'route' => 'notifications.index', 'icon' => 'bell', 'match' => 'notifications*', 'show' => true],
                ['label' => 'سجل التدقيق', 'route' => 'audit.index', 'icon' => 'history', 'match' => 'audit*', 'show' => $can('audit.view')],
                ['label' => 'الإعدادات', 'route' => 'settings.index', 'icon' => 'settings', 'match' => 'settings*', 'show' => $can('settings.edit')],
            ],
        ],
    ];

    foreach ($sidebarModules as &$module) {
        foreach ($module['items'] as &$item) {
            $item['active'] = request()->routeIs($item['route']) || request()->is($item['match']);
        }
        unset($item);
        $module['hasActive'] = collect($module['items'])->contains(fn($i) => $i['active'] && $i['show']);
    }
    unset($module);
@endphp

<aside
    x-data="sidebarNav()"
    x-init="init()"
    @mouseenter="if(!isMobile) hovered = true"
    @mouseleave="if(!isMobile) hovered = false"
    :class="{
        'sidebar-collapsed w-[72px]': !isMobile && collapsed && !hovered,
        'w-[260px]': !isMobile && (!collapsed || hovered),
        'translate-x-0 w-[280px] shadow-2xl': isMobile && sidebarMobileOpen,
        'translate-x-full w-[280px]': isMobile && !sidebarMobileOpen,
    }"
    class="br-sidebar fixed inset-y-0 right-0 z-50 flex flex-col border-l border-white/10 transition-all duration-300 ease-in-out md:translate-x-0"
    dir="rtl"
    aria-label="القائمة الجانبية"
>
    <style>
        .br-sidebar { background: var(--sidebar-bg); color: var(--sidebar-text); }
        .br-sidebar .nav-scroll { scrollbar-width: thin; scrollbar-color: rgba(255,255,255,.15) transparent; }
        .br-sidebar .nav-scroll::-webkit-scrollbar { width: 4px; }
        .br-sidebar .nav-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 4px; }
        .br-brand { height: 72px; padding: 0 16px; border-bottom: 1px solid rgba(255,255,255,.08); display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .br-brand-mark { width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(145deg, rgba(21,157,145,.95), rgba(17,127,118,.95)); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; box-shadow: 0 10px 24px -12px rgba(21,157,145,.9); flex-shrink: 0; }
        .br-brand-title { font-size: 15px; font-weight: 800; color: #fff; line-height: 1.1; white-space: nowrap; }
        .br-brand-sub { font-size: 10px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: rgba(255,255,255,.5); white-space: nowrap; }
        .br-group { padding: 0 12px; margin-top: 14px; }
        .br-group-label { display: flex; align-items: center; gap: 8px; padding: 0 10px; margin-bottom: 6px; font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: rgba(255,255,255,.42); }
        .br-group-label::after { content: ''; flex: 1; height: 1px; background: linear-gradient(90deg, rgba(255,255,255,.1), transparent); }
        .br-nav-item {
            display: flex; align-items: center; gap: 12px; width: 100%;
            padding: 10px 12px; border-radius: 10px; color: rgba(255,255,255,.82);
            font-size: 13.5px; font-weight: 500; text-decoration: none; position: relative;
            transition: background .15s, color .15s, box-shadow .15s; min-height: 42px;
        }
        .br-nav-item:hover { background: rgba(255,255,255,.08); color: #fff; }
        .br-nav-item.active { background: rgba(21,157,145,.18); color: #fff; font-weight: 700; box-shadow: inset 0 0 0 1px rgba(21,157,145,.28); }
        .br-nav-item.active::before { content: ''; position: absolute; right: 0; top: 8px; bottom: 8px; width: 3px; border-radius: 4px; background: #2dd4bf; }
        .br-nav-icon { width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; color: rgba(255,255,255,.72); }
        .br-nav-item:hover .br-nav-icon, .br-nav-item.active .br-nav-icon { color: #fff; }
        .br-nav-label { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: right; }
        .br-footer { padding: 12px; border-top: 1px solid rgba(255,255,255,.08); }
        .br-collapse-btn {
            width: 100%; display: inline-flex; align-items: center; gap: 10px; padding: 10px 12px;
            border-radius: 10px; border: 0; background: transparent; color: rgba(255,255,255,.65);
            font: inherit; font-size: 13px; font-weight: 600; cursor: pointer; transition: .15s;
        }
        .br-collapse-btn:hover { background: rgba(255,255,255,.08); color: #fff; }
        .sidebar-collapsed .br-hide-collapsed { display: none !important; }
        .sidebar-collapsed .br-group { padding: 0 8px; margin-top: 10px; }
        .sidebar-collapsed .br-group-label { justify-content: center; padding: 0; }
        .sidebar-collapsed .br-group-label::after { display: none; }
        .sidebar-collapsed .br-nav-item { justify-content: center; padding: 10px; }
        .sidebar-collapsed .br-brand { justify-content: center; padding: 0 8px; }
        .sidebar-collapsed .br-collapse-btn { justify-content: center; padding: 10px; }
        @media (max-width: 767px) {
            .br-sidebar { transform: translateX(100%); }
            .br-sidebar.translate-x-0 { transform: translateX(0); }
        }
    </style>

    <div class="br-brand">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-w-0 overflow-hidden">
            <span class="br-brand-mark">BR</span>
            <span class="br-hide-collapsed min-w-0">
                <span class="block br-brand-title">By Raslan</span>
                <span class="block br-brand-sub">ERP System</span>
            </span>
        </a>
        <button type="button" class="br-hide-collapsed md:hidden inline-flex h-9 w-9 items-center justify-center rounded-lg text-white/60 hover:bg-white/10 hover:text-white" @click="$dispatch('sidebar-close')" aria-label="إغلاق القائمة">
            <x-icon name="x" size="18" />
        </button>
    </div>

    <nav class="nav-scroll flex-1 overflow-y-auto py-2">
        @foreach($sidebarModules as $module)
            @php $visible = collect($module['items'])->where('show', true)->values(); @endphp
            @if($visible->isNotEmpty())
                <div class="br-group">
                    <div class="br-group-label"><span class="br-hide-collapsed">{{ $module['label'] }}</span></div>
                    <div class="space-y-1">
                        @foreach($visible as $item)
                            @php $active = $item['active']; @endphp
                            <a href="{{ route($item['route']) }}"
                               class="br-nav-item {{ $active ? 'active' : '' }}"
                               :title="(!isMobile && collapsed && !hovered) ? @js($item['label']) : null"
                               @if($active) aria-current="page" @endif>
                                <span class="br-nav-icon"><x-icon :name="$item['icon']" size="20" stroke="1.8" /></span>
                                <span class="br-nav-label br-hide-collapsed">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </nav>

    <div class="br-footer hidden md:block">
        <button type="button" class="br-collapse-btn" @click="collapsed = !collapsed" :aria-label="collapsed ? 'توسيع القائمة' : 'طي القائمة'">
            <span class="inline-flex w-5 h-5 items-center justify-center shrink-0">
                <x-icon name="panel-right-open" size="18" x-show="collapsed" x-cloak />
                <x-icon name="panel-right-close" size="18" x-show="!collapsed" />
            </span>
            <span class="br-hide-collapsed" x-text="collapsed ? 'توسيع القائمة' : 'طي القائمة'"></span>
        </button>
    </div>
</aside>

<script>
function sidebarNav() {
    return {
        collapsed: localStorage.getItem('br.sidebar') !== '0',
        hovered: false,
        isMobile: window.innerWidth < 768,
        init() {
            this.$watch('collapsed', v => localStorage.setItem('br.sidebar', v ? '1' : '0'));
            window.addEventListener('resize', () => { this.isMobile = window.innerWidth < 768; });
        }
    }
}
</script>
