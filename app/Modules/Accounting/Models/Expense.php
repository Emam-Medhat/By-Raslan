<?php

namespace App\Modules\Accounting\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    public const UPDATED_AT = null;

    public const CREATED_AT = 'at';

    public const STATUSES = ['ok', 'pending', 'rejected'];

    protected $fillable = [
        'no', 'day', 'location_id', 'category_id', 'amount', 'paid_from', 'shift_id',
        'note', 'photo', 'status', 'by', 'at', 'decided_by', 'decided_at', 'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'day' => 'date',
            'at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'by');
    }
}