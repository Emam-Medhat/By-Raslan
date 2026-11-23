<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['auth_id', 'phone', 'name', 'address', 'area', 'notes'];

    public function orders(): HasMany
    {
        return $this->hasMany(OnlineOrder::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }
}