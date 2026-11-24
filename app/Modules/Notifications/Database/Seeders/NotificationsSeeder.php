<?php

namespace App\Modules\Notifications\Database\Seeders;

use App\Modules\Notifications\Models\PushToken;
use App\Models\User;
use App\Modules\Orders\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Push Tokens for users
            $users = User::all();
            foreach ($users as $user) {
                PushToken::updateOrCreate(
                    ['user_id' => $user->id, 'platform' => 'ios'],
                    ['token' => 'ExponentPushToken[' . Str::random(20) . ']', 'customer_id' => null]
                );
                PushToken::updateOrCreate(
                    ['user_id' => $user->id, 'platform' => 'android'],
                    ['token' => 'ExponentPushToken[' . Str::random(20) . ']', 'customer_id' => null]
                );
            }

            // Push Tokens for customers
            $customers = Customer::all();
            foreach ($customers->take(30) as $customer) {
                PushToken::updateOrCreate(
                    ['customer_id' => $customer->id, 'platform' => 'ios'],
                    ['token' => 'ExponentPushToken[' . Str::random(20) . ']', 'user_id' => null]
                );
                PushToken::updateOrCreate(
                    ['customer_id' => $customer->id, 'platform' => 'android'],
                    ['token' => 'ExponentPushToken[' . Str::random(20) . ']', 'user_id' => null]
                );
            }

            // In-app notifications (using Laravel's notifications table)
            $notifiableUsers = $users->merge($customers);
            
            $notificationTypes = [
                'App\\Notifications\\OrderStatusChanged',
                'App\\Notifications\\ShiftClosed',
                'App\\Notifications\\TransferCreated',
                'App\\Notifications\\ProductionOrderReady',
                'App\\Notifications\\ExpenseApproved',
                'App\\Notifications\\PurchaseOrderReceived',
                'App\\Notifications\\LowStockAlert',
                'App\\Notifications\\SalesReturnApproved',
            ];

            $notificationData = [
                'App\\Notifications\\OrderStatusChanged' => [
                    'title' => 'تحديث حالة الطلب',
                    'body' => 'تم تحديث حالة طلبك رقم',
                    'order_id' => 1,
                ],
                'App\\Notifications\\ShiftClosed' => [
                    'title' => 'إغلاق وردية',
                    'body' => 'تم إغلاق وردية الفرع',
                    'shift_id' => 1,
                ],
                'App\\Notifications\\TransferCreated' => [
                    'title' => 'تحويل جديد',
                    'body' => 'تم إنشاء تحويل من الفرع',
                    'transfer_id' => 1,
                ],
                'App\\Notifications\\ProductionOrderReady' => [
                    'title' => 'طلب إنتاج جاهز',
                    'body' => 'أمر الإنتاج رقم',
                    'prod_order_id' => 1,
                ],
                'App\\Notifications\\ExpenseApproved' => [
                    'title' => 'مصروف معتمد',
                    'body' => 'تم اعتماد مصروفك',
                    'expense_id' => 1,
                ],
                'App\\Notifications\\PurchaseOrderReceived' => [
                    'title' => 'استلام توريد',
                    'body' => 'تم استلام أمر التوريد',
                    'po_id' => 1,
                ],
                'App\\Notifications\\LowStockAlert' => [
                    'title' => 'تنبيه مخزون منخفض',
                    'body' => 'مخزون الصنف وصل للحد الأدنى',
                    'item_id' => 1,
                ],
                'App\\Notifications\\SalesReturnApproved' => [
                    'title' => 'مرتجع مبيعات معتمد',
                    'body' => 'تم اعتماد مرتجع المبيعات',
                    'return_id' => 1,
                ],
            ];

            for ($i = 1; $i <= 200; $i++) {
                $notifiable = $notifiableUsers->random();
                $type = $notificationTypes[array_rand($notificationTypes)];
                $data = $notificationData[$type] ?? ['message' => 'إشعار جديد'];
                $data['id'] = $i;
                
                DB::table('notifications')->insert([
                    'id' => Str::uuid(),
                    'type' => $type,
                    'notifiable_type' => get_class($notifiable),
                    'notifiable_id' => $notifiable->id,
                    'data' => json_encode($data),
                    'read_at' => rand(1, 100) > 40 ? now()->subDays(rand(0, 30)) : null,
                    'created_at' => now()->subDays(rand(0, 30)),
                    'updated_at' => now()->subDays(rand(0, 30)),
                ]);
            }
        });
    }
}