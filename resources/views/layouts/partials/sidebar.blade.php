@php
    $groups = [
        ['', [
            ['الرئيسية', '/dashboard', '🏠'],
            ['الإحصائيات', '/statistics', '📊'],
        ]],
        ['البيع', [
            ['طلبات الأونلاين', '/orders', '🛒'],
            ['الكاشير', '/pos', '💳'],
            ['المبيعات', '/sales', '🧾'],
            ['المرتجعات', '/returns', '↩️'],
            ['الورديات', '/shifts', '🕒'],
            ['أسعار الفروع', '/prices', '🏷️'],
        ]],
        ['المصنع والمخزون', [
            ['أوامر الإنتاج', '/production', '⚙️'],
            ['تابلت الغرف', '/station', '🧱'],
            ['محطة التغليف', '/pack', '📦'],
            ['تحميل العربيات', '/trips', '🚚'],
            ['المخزون', '/stock', '📦'],
            ['التحويلات', '/transfers', '🔁'],
            ['هالك وتبرعات', '/stock/dispose', '🗑️'],
            ['تتبّع العلبة', '/boxes', '🔎'],
        ]],
        ['المشتريات', [
            ['أوامر التوريد', '/purchasing', '🛒'],
            ['إيصالات الاستلام', '/purchasing/receipts', '📄'],
            ['كارت الصنف (الجودة)', '/purchasing/specs', '🧾'],
            ['الموردين', '/purchasing/suppliers', '🏢'],
            ['مرتجعات الموردين', '/purchase-returns', '↩️'],
            ['التجاوزات', '/overrides', '⚙️'],
        ]],
        ['الحسابات', [
            ['الملخص اليومي', '/accounts', '💳'],
            ['المصروفات', '/accounts/expenses', '💸'],
            ['الورديات', '/shifts', '🧾'],
        ]],
        ['الأصناف', [
            ['الأصناف والعائلات', '/items', '🧾'],
        ]],
        ['الإعداد', [
            ['المواقع', '/locations', '🏢'],
            ['الصناديق', '/crates', '📦'],
            ['المستخدمين والصلاحيات', '/users', '🧩'],
            ['الإشعارات', '/notifications', '🔔'],
            ['السجل', '/audit', '📜'],
            ['الإعدادات', '/settings', '⚙️'],
        ]],
    ];
@endphp

<aside x-data="{ open: false }"
       class="fixed inset-y-0 right-0 z-30 w-64 bg-white border-l border-slate-200 transform transition-transform md:translate-x-0"
       :class="open ? 'translate-x-0' : 'translate-x-full'">
    <div class="h-16 flex items-center justify-between px-4 border-b border-slate-100">
        <span class="font-extrabold text-lg text-brand-700">BY RASLAN ERP</span>
        <button class="md:hidden text-slate-500" @click="open = false">✕</button>
    </div>

    <nav class="h-[calc(100vh-4rem)] overflow-y-auto py-2">
        @foreach ($groups as [$label, $items])
            @if ($label)
                <div class="px-4 pt-4 pb-1 text-xs font-bold text-slate-400">{{ $label }}</div>
            @endif
            @foreach ($items as [$itemLabel, $href, $icon])
                <a href="{{ $href }}"
                   class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-slate-50 {{ request()->is(ltrim($href,'/')) ? 'bg-brand-600/10 text-brand-700 font-semibold' : '' }}">
                    <span>{{ $icon }}</span>
                    <span>{{ $itemLabel }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>
</aside>