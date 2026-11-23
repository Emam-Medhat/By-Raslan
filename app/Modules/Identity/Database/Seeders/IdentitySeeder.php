<?php

namespace App\Modules\Identity\Database\Seeders;

use App\Models\User;
use App\Modules\Identity\Models\Permission;
use App\Modules\Identity\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the permission catalog, the 9 role templates (ported from the legacy
 * src/lib/roles.ts) and a first owner account.
 */
class IdentitySeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['users.manage', 'الإعداد', 'إدارة المستخدمين والصلاحيات'],
            ['settings.edit', 'الإعداد', 'تعديل الإعدادات والأرقام'],
            ['audit.view', 'الإعداد', 'سجل التعديلات'],
            ['items.edit', 'الأصناف', 'إضافة وتعديل الأصناف والعائلات'],
            ['prices.edit', 'الأصناف', 'تعديل الأسعار'],
            ['stock.view', 'المخزون', 'عرض المخزون'],
            ['stock.edit', 'المخزون', 'المواقع والتحويلات'],
            ['stock.adjust', 'المخزون', 'إضافة/خصم يدوي من المخزون'],
            ['stock.waste', 'المخزون', 'تحويل لهالك'],
            ['stock.donate', 'المخزون', 'تحويل وخروج التبرعات'],
            ['prod.orders', 'الإنتاج', 'أوامر التشغيل'],
            ['prod.pack', 'الإنتاج', 'التغليف والطباعة'],
            ['trips.load', 'التوزيع', 'تحميل العربيات'],
            ['pos.sell', 'البيع', 'البيع في الفرع'],
            ['pos.discount', 'البيع', 'خصم على الفاتورة'],
            ['pos.shift', 'البيع', 'إدارة الورديات'],
            ['hr.view', 'الموارد البشرية', 'عرض الموظفين'],
            ['hr.pay', 'الموارد البشرية', 'المرتبات والبدلات والخصومات'],
            ['acc.view', 'الحسابات', 'عرض الحسابات'],
            ['acc.post', 'الحسابات', 'تسجيل قيود ومصروفات'],
            ['acc.pay', 'الحسابات', 'دفع الموردين'],
            ['acc.approve', 'الحسابات', 'اعتماد المصروفات'],
            ['orders.handle', 'الطلبات', 'إدارة طلبات الأونلاين'],
            ['orders.cancel', 'الطلبات', 'إلغاء الطلبات'],
            ['purchase.edit', 'المشتريات', 'أوامر التوريد'],
            ['override.recv', 'المشتريات', 'استلام بتجاوز'],
        ];

        foreach ($permissions as [$id, $grp, $label]) {
            Permission::updateOrCreate(['id' => $id], ['grp' => $grp, 'label' => $label]);
        }

        $roles = [
            ['cashier', 'كاشير', ['pos.sell', 'stock.view']],
            ['branch', 'مسؤول فرع', ['orders.handle', 'orders.cancel', 'acc.post', 'pos.sell', 'stock.view', 'stock.edit', 'stock.adjust', 'stock.waste', 'stock.donate', 'hr.view']],
            ['packer', 'عامل تغليف', ['prod.pack']],
            ['room', 'عامل غرفة إنتاج', ['prod.orders']],
            ['online', 'تحضير طلبات أونلاين', ['orders.handle', 'stock.view']],
            ['loader', 'تحميل وسواق', ['trips.load', 'stock.view']],
            ['storekeeper', 'أمين مخزن', ['stock.view', 'stock.edit', 'stock.adjust', 'stock.waste', 'stock.donate', 'purchase.edit']],
            ['factory', 'مدير مصنع', ['prod.orders', 'prod.pack', 'trips.load', 'stock.view', 'stock.edit', 'stock.adjust', 'stock.waste', 'items.edit', 'override.recv']],
            ['accountant', 'محاسب', ['acc.view', 'acc.post', 'acc.pay', 'acc.approve', 'hr.view', 'stock.view']],
        ];

        foreach ($roles as [$slug, $name, $perms]) {
            $role = Role::updateOrCreate(['slug' => $slug], ['name' => $name]);
            $role->permissions()->sync($perms);
        }

        User::firstOrCreate(
            ['phone' => '01000000000'],
            [
                'name' => 'المالك',
                'password' => Hash::make('password'),
                'is_owner' => true,
                'active' => true,
            ],
        );
    }
}