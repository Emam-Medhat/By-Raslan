<?php

namespace App\Modules\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['name', 'phone', 'terms', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}