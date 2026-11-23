<?php

namespace App\Modules\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;

class OverrideRequest extends Model
{
    public const STATUSES = ['pending', 'approved', 'rejected', 'used'];

    protected $fillable = ['kind', 'ref', 'violations', 'value', 'reason', 'status', 'decided_by', 'decided_at', 'decision_note', 'created_by'];

    protected function casts(): array
    {
        return ['violations' => 'array', 'value' => 'decimal:2', 'decided_at' => 'datetime'];
    }
}