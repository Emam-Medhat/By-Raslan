<?php

namespace App\Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdStep extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'step', 'section_id', 'status', 'in_weight', 'out_weight', 'waste',
        'started_at', 'ended_at', 'started_by', 'ended_by',
    ];

    protected function casts(): array
    {
        return [
            'in_weight' => 'decimal:3',
            'out_weight' => 'decimal:3',
            'waste' => 'decimal:3',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Organization\Models\Location::class, 'section_id');
    }
}