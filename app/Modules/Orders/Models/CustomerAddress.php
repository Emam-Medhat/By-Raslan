<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $fillable = ['customer_id', 'label', 'area', 'address', 'location_id', 'is_default', 'active'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'active' => 'boolean'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
