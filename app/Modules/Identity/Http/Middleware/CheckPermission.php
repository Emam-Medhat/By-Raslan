<?php

namespace App\Modules\Identity\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route guard: requires the authenticated user to hold a permission.
 * Usage: ->middleware('perm:stock.adjust')
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasPermission($permission)) {
            abort(403, 'لا تملك الصلاحية المطلوبة');
        }

        return $next($request);
    }
}