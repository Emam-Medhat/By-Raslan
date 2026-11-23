<?php

namespace App\Modules\Audit\Services;

use App\Modules\Audit\Models\AuditLog;

class AuditService
{
    public function log(string $action, ?string $table = null, ?string $recordId = null, ?array $old = null, ?array $new = null, ?string $by = null): AuditLog
    {
        return AuditLog::create([
            'at' => now(),
            'by' => $by ?? auth()->id(),
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'old' => $old,
            'new' => $new,
        ]);
    }
}