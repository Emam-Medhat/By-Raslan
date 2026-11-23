<?php

namespace App\Modules\Transfers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferBox extends Model
{
    protected $fillable = ['transfer_id', 'box_sn', 'state'];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }
}