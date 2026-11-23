<?php

namespace App\Modules\Trips\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripBox extends Model
{
    public $timestamps = false;

    protected $fillable = ['trip_id', 'crate_id', 'box_sn', 'removed', 'at', 'by'];

    protected function casts(): array
    {
        return ['removed' => 'boolean', 'at' => 'datetime'];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}