<?php

namespace App\Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;

class ItemRoute extends Model
{
    public $timestamps = false;

    protected $fillable = ['item_id', 'ver', 'step', 'section_id'];
}