<?php

namespace App\Modules\Trips\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    public const STATUSES = ['loading', 'out', 'done'];

    protected $fillable = ['no', 'truck', 'driver', 'status', 'created_by', 'left_at'];

    protected function casts(): array
    {
        return ['left_at' => 'datetime'];
    }

    public function crates(): HasMany
    {
        return $this->hasMany(TripCrate::class);
    }

    public function boxes(): HasMany
    {
        return $this->hasMany(TripBox::class);
    }
}