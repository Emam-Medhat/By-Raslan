<?php

namespace App\Modules\Identity\Support;

class ModuleRegistry
{
    /**
     * Sidebar-aligned modules. Each module maps to permission keys.
     * Permissions listed here are the module's primary permissions;
     * shared view permissions (stock.view, acc.view) are included only
     * where the module is the natural owner, to avoid cross-module bleed.
     */
    public static function modules(): array
    {
        return [
            [
                'key' => 'dashboard',
                'label' => 'الرئيسية',
                'icon' => 'layout-dashboard',
                'description' => 'لوحة التحكم',
                'permissions' => [],
                'always_visible' => true,
            ],
            [
                'key' => 'sales',
                'label' => 'المبيعات',
                'icon' => 'shopping-cart',
                'description' => 'الكاشير، المبيعات، المرتجعات، الطلبات، الأسعار',
                'permissions' => ['pos.sell', 'pos.discount', 'pos.shift', 'orders.handle', 'orders.cancel', 'prices.edit'],
            ],
            [
                'key' => 'inventory',
                'label' => 'المخزون',
                'icon' => 'boxes',
                'description' => 'الأصناف، المخزون، التحويلات، الجرد، تتبع العلبة، الهالك',
                'permissions' => ['stock.view', 'stock.edit', 'stock.adjust', 'stock.waste', 'stock.donate'],
            ],
            [
                'key' => 'purchasing',
                'label' => 'المشتريات',
                'icon' => 'shopping-bag',
                'description' => 'أوامر التوريد، الموردين، مرتجعات الموردين، المواصفات، الاستثناءات',
                'permissions' => ['purchase.edit', 'override.recv'],
            ],
            [
                'key' => 'production',
                'label' => 'الإنتاج',
                'icon' => 'factory',
                'description' => 'أوامر الإنتاج، المسارات، شاشات الإنتاج، التغليف، تحميل العربيات',
                'permissions' => ['prod.orders', 'prod.pack', 'trips.load', 'items.edit'],
            ],
            [
                'key' => 'accounting',
                'label' => 'الحسابات',
                'icon' => 'landmark',
                'description' => 'الحسابات العامة، الصناديق، الموردين، المصروفات، الورديات',
                'permissions' => ['acc.view', 'acc.post', 'acc.pay', 'acc.approve'],
            ],
            [
                'key' => 'admin',
                'label' => 'الإدارة',
                'icon' => 'settings',
                'description' => 'المواقع، المستخدمين، الأدوار، التدقيق، الإعدادات',
                'permissions' => ['users.manage', 'settings.edit', 'audit.view'],
            ],
        ];
    }

    public static function permissionToModules(): array
    {
        $map = [];
        foreach (self::modules() as $m) {
            foreach ($m['permissions'] as $p) {
                $map[$p][] = $m['key'];
            }
        }
        return $map;
    }

    public static function allPermissionIds(): array
    {
        $ids = [];
        foreach (self::modules() as $m) {
            foreach ($m['permissions'] as $p) $ids[] = $p;
        }
        return array_values(array_unique($ids));
    }

    public static function permissionsForModules(array $moduleKeys): array
    {
        $perms = [];
        foreach (self::modules() as $m) {
            if (in_array($m['key'], $moduleKeys, true)) {
                foreach ($m['permissions'] as $p) $perms[] = $p;
            }
        }
        return array_values(array_unique($perms));
    }
}
