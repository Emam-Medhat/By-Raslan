<?php

namespace App\Modules\Sales\Http\Middleware;

use App\Modules\Sales\Models\Shift;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strict shift lock: a pure cashier (pos.sell, without pos.shift) may not run
 * financial/operational actions without an open shift. Owners and shift
 * managers (pos.shift) bypass. This is a server-side backstop — the primary
 * lock is session invalidation on close.
 */
class EnsureActiveShift
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isOwner()) {
            return $next($request);
        }

        // Only pure cashiers are shift-bound.
        if (! $user->hasPermission('pos.sell') || $user->hasPermission('pos.shift')) {
            return $next($request);
        }

        $hasOpen = Shift::where('cashier', $user->id)->where('status', 'open')->exists();

        if (! $hasOpen) {
            abort(403, 'لا يوجد شيفت نشط — سجّل الدخول من جديد لفتح شيفت');
        }

        return $next($request);
    }
}