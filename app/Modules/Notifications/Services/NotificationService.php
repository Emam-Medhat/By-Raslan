<?php

namespace App\Modules\Notifications\Services;

use App\Modules\Notifications\Jobs\SendExpoPushJob;
use App\Modules\Notifications\Models\PushToken;
use App\Modules\Orders\Models\OnlineOrder;

class NotificationService
{
    public function push(?int $userId, ?int $customerId, string $title, string $body): void
    {
        $query = PushToken::query();

        if ($customerId !== null) {
            $query->where('customer_id', $customerId);
        } elseif ($userId !== null) {
            $query->where('user_id', $userId);
        } else {
            return;
        }

        $tokens = $query->pluck('token')->all();

        if ($tokens !== []) {
            SendExpoPushJob::dispatch($tokens, $title, $body);
        }
    }

    public function orderStatus(OnlineOrder $order): void
    {
        $labels = [
            'accepted' => 'تم قبول طلبك وبدأ التحضير',
            'preparing' => 'طلبك قيد التحضير',
            'ready' => 'طلبك جاهز للخروج',
            'out' => 'طلبك خرج مع الطيار',
            'delivered' => 'تم تسليم طلبك',
            'cancelled' => 'تم إلغاء طلبك',
        ];

        $this->push(null, $order->customer_id, 'تحديث الطلب #'.$order->no, $labels[$order->status] ?? $order->status);
    }
}