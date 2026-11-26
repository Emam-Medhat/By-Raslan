<?php

namespace App\Modules\Identity\Support;

class AccessRegistry
{
    public static function groups(): array
    {
        return [
            ['key' => 'home', 'label' => 'الرئيسية', 'links' => [
                self::l('nav.dashboard', 'لوحة التحكم', 'layout-dashboard', 'dashboard'),
                self::l('nav.statistics', 'الإحصائيات', 'chart-no-axes-combined', 'statistics.index'),
            ]],
            ['key' => 'sales', 'label' => 'المبيعات', 'links' => [
                self::l('nav.pos', 'الكاشير', 'monitor', 'pos.index'),
                self::l('nav.sales', 'المبيعات', 'receipt-text', 'sales.index'),
                self::l('nav.returns', 'المرتجعات', 'undo-2', 'returns.index'),
                self::l('nav.orders', 'طلبات الأونلاين', 'shopping-cart', 'orders.index'),
                self::l('nav.collections', 'استلام وتحصيل', 'hand-coins', 'orders.collections'),
                self::l('nav.reservations', 'الحجوزات', 'calendar-clock', 'reservations.index'),
                self::l('nav.prices', 'الأسعار', 'badge-dollar-sign', 'prices.index'),
            ]],
            ['key' => 'inventory', 'label' => 'المخزون', 'links' => [
                self::l('nav.items', 'الأصناف', 'package-search', 'items.index'),
                self::l('nav.stock', 'المخزون والحركات', 'boxes', 'stock.index'),
                self::l('nav.transfers', 'التحويلات', 'arrow-left-right', 'transfers.index'),
                self::l('nav.stock_counts', 'الجرد', 'clipboard-check', 'stock-counts.index'),
                self::l('nav.boxes', 'تتبّع العلبة', 'qr-code', 'boxes.index'),
                self::l('nav.dispose', 'هالك وتلف', 'trash-2', 'stock.dispose'),
            ]],
            ['key' => 'purchasing', 'label' => 'المشتريات', 'links' => [
                self::l('nav.purchasing', 'أوامر التوريد', 'shopping-bag', 'purchasing.index'),
                self::l('nav.suppliers', 'الموردين', 'truck', 'purchasing.suppliers.index'),
                self::l('nav.purchase_returns', 'مرتجعات الموردين', 'undo-2', 'purchase-returns.index'),
                self::l('nav.specs', 'المواصفات', 'file-text', 'purchasing.specs.index'),
                self::l('nav.overrides', 'الاستثناءات', 'shield-check', 'overrides.index'),
            ]],
            ['key' => 'production', 'label' => 'الإنتاج', 'links' => [
                self::l('nav.production', 'أوامر الإنتاج', 'factory', 'production.index'),
                self::l('nav.production_routes', 'مسارات الإنتاج', 'git-branch', 'production.routes'),
                self::l('nav.production_station', 'شاشات الإنتاج', 'monitor', 'production.station'),
                self::l('nav.pack', 'محطة التغليف', 'package-open', 'pack.index'),
                self::l('nav.trips', 'تحميل العربيات', 'truck', 'trips.index'),
            ]],
            ['key' => 'accounting', 'label' => 'الحسابات', 'links' => [
                self::l('nav.accounts', 'الحسابات العامة', 'landmark', 'accounts.index'),
                self::l('nav.cash_transfers', 'الصناديق والتحويلات', 'wallet', 'cash-transfers.index'),
                self::l('nav.expenses', 'المصروفات', 'banknote', 'expenses.index'),
                self::l('nav.shifts', 'الورديات', 'clock', 'shifts.index'),
                self::l('nav.shift_expenses', 'مصروفات الورديات', 'receipt-text', 'shift-expenses.index'),
            ]],
            ['key' => 'admin', 'label' => 'الإدارة', 'links' => [
                self::l('nav.locations', 'المواقع والفروع', 'store', 'locations.index'),
                self::l('nav.users', 'المستخدمون', 'users', 'users.index'),
                self::l('nav.roles', 'الأدوار والصلاحيات', 'shield-check', 'roles.index'),
                self::l('nav.notifications', 'الإشعارات', 'bell', 'notifications.index'),
                self::l('nav.audit', 'سجل التدقيق', 'history', 'audit.index'),
                self::l('nav.settings', 'الإعدادات', 'settings', 'settings.index'),
            ]],
        ];
    }

    private static function l(string $permission, string $label, string $icon, string $route): array
    {
        return compact('permission', 'label', 'icon', 'route');
    }

    public static function allLinks(): array
    {
        $out = [];
        foreach (self::groups() as $g) {
            foreach ($g['links'] as $l) { $l['group'] = $g['label']; $l['group_key'] = $g['key']; $out[] = $l; }
        }
        return $out;
    }

    public static function permissionIds(): array
    {
        return array_column(self::allLinks(), 'permission');
    }

    public static function permissionForRoute(string $routeName): ?string
    {
        static $map;
        if ($map === null) {
            $map = [];
            foreach (self::allLinks() as $l) $map[$l['route']] = $l['permission'];
        }
        return $map[$routeName] ?? null;
    }

    /** Map nav permission to underlying business permissions it should imply. */
    public static function impliedPermissions(string $navPermission): array
    {
        return self::impliedMap()[$navPermission] ?? [];
    }

    public static function impliedMap(): array
    {
        return [
            'nav.dashboard' => [],
            'nav.statistics' => ['acc.view'],
            'nav.pos' => ['pos.sell'],
            'nav.sales' => ['acc.view'],
            'nav.returns' => ['acc.view'],
            'nav.orders' => ['orders.handle'],
            'nav.collections' => ['orders.handle','acc.view'],
            'nav.reservations' => ['orders.handle'],
            'nav.prices' => ['prices.edit'],
            'nav.items' => ['stock.view'],
            'nav.stock' => ['stock.view', 'stock.adjust'],
            'nav.transfers' => ['stock.view', 'stock.edit'],
            'nav.stock_counts' => ['stock.view', 'stock.adjust'],
            'nav.boxes' => ['stock.view'],
            'nav.dispose' => ['stock.adjust'],
            'nav.purchasing' => ['purchase.edit', 'stock.view'],
            'nav.suppliers' => ['purchase.edit', 'acc.pay'],
            'nav.purchase_returns' => ['purchase.edit', 'acc.view'],
            'nav.specs' => ['purchase.edit'],
            'nav.overrides' => ['override.recv', 'purchase.edit'],
            'nav.production' => ['prod.orders', 'stock.view'],
            'nav.production_routes' => ['items.edit'],
            'nav.production_station' => ['prod.orders'],
            'nav.pack' => ['prod.pack'],
            'nav.trips' => ['trips.load'],
            'nav.accounts' => ['acc.view'],
            'nav.cash_transfers' => ['acc.view'],
            'nav.expenses' => ['acc.view', 'acc.post', 'acc.approve'],
            'nav.shifts' => ['acc.view'],
            'nav.shift_expenses' => ['acc.view'],
            'nav.locations' => ['settings.edit'],
            'nav.users' => ['users.manage'],
            'nav.roles' => ['users.manage'],
            'nav.notifications' => [],
            'nav.audit' => ['audit.view'],
            'nav.settings' => ['settings.edit'],
        ];
    }

    public static function expandPermissions(array $permissions): array
    {
        $set = [];
        foreach ($permissions as $p) {
            $set[$p] = true;
            foreach (self::impliedPermissions($p) as $implied) $set[$implied] = true;
        }
        return array_keys($set);
    }
}
