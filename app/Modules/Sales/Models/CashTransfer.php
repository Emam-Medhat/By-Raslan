<?php

namespace App\Modules\Sales\Models;

use App\Models\User;
use App\Modules\Organization\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cash transfer: money moved between shifts, to manager, or to safe.
 * Mirrors Nano City's CashTransfer model.
 */
class CashTransfer extends Model
{
    public const TYPES = [
        'to_manager' => 'تسليم للمدير',
        'to_next_shift' => 'ترحيل للوردية التالية',
        'to_safe' => 'إيداع في الخزنة',
    ];

    public const STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = [
        'from_shift_id',
        'to_shift_id',
        'location_id',
        'from_user_id',
        'to_user_id',
        'type',
        'amount',
        'status',
        'approved_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function fromShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'from_shift_id');
    }

    public function toShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'to_shift_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'قيد المراجعة',
            'approved' => 'معتمد',
            'rejected' => 'مرفوض',
            default => $this->status,
        };
    }
}