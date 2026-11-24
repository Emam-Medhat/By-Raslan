<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Organization\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockCount extends Model
{
    public const STATUSES = ['draft', 'in_progress', 'completed', 'approved'];

    protected $fillable = [
        'location_id',
        'user_id',
        'count_number',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockCountItem::class);
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'مسودة',
            'in_progress' => 'جاري',
            'completed' => 'مكتمل',
            'approved' => 'معتمد',
            default => $this->status,
        };
    }
}