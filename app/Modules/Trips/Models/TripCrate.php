<?php

namespace App\Modules\Trips\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripCrate extends Model
{
    protected $fillable = ['trip_id', 'crate_id', 'to_loc', 'closed'];

    protected function casts(): array
    {
        return ['closed' => 'boolean'];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}