<?php

namespace App\Modules\Sales\Http\Middleware;

use App\Modules\Sales\Models\Shift;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * POS transactions must always belong to an open cashier shift.
 */
class EnsureActiveShift
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $hasOpen = Shift::where('cashier', $user->id)->where('status', 'open')->exists();

        if (! $hasOpen) {
            abort(403, 'لا يوجد شيفت نشط — سجّل الدخول من جديد لفتح شيفت');
        }

        return $next($request);
    }
}
