<?php

namespace App\Modules\Trips\Models;

use Illuminate\Database\Eloquent\Model;

class Crate extends Model
{
    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['id', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}