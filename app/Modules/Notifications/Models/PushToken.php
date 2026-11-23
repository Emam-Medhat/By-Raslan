<?php

namespace App\Modules\Notifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushToken extends Model
{
    protected $fillable = ['user_id', 'customer_id', 'token', 'platform'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}