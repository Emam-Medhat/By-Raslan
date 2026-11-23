<?php

namespace App\Modules\Audit\Http\Controllers;

use App\Modules\Audit\Models\AuditLog;
use Illuminate\View\View;

class AuditController
{
    public function index(): View
    {
        $logs = AuditLog::with('user')->orderByDesc('id')->paginate(30);

        return view('audit.index', compact('logs'));
    }
}