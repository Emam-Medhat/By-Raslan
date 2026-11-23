<?php

namespace App\Modules\Audit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const CREATED_AT = 'at';

    protected $table = 'audit_log';

    protected $fillable = ['by', 'action', 'table_name', 'record_id', 'old', 'new', 'shift_id'];

    protected function casts(): array
    {
        return ['old' => 'array', 'new' => 'array', 'at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'by');
    }
}