<?php

namespace App\Modules\Identity\Http\Middleware;

use App\Modules\Identity\Support\AccessRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSidebarAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->isOwner()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $path = ltrim($request->path(), '/');
        $required = null;

        foreach (AccessRegistry::allLinks() as $link) {
            foreach ($link['patterns'] as $pattern) {
                if ($request->is($pattern)) {
                    $required = $link['permission'];
                    break 2;
                }
            }
            // fallback by route name prefix
            $match = $link['match'];
            $prefix = rtrim(str_replace(['*', '$'], '', $match), '/');
            if ($routeName && $prefix && str_starts_with($routeName, explode('.', $link['route'])[0] . '.')) {
                // too broad; skip
            }
        }

        if ($required && ! $user->hasPermission($required)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'forbidden', 'message' => 'ليس لديك صلاحية.'], 403);
            }
            abort(403, 'ليس لديك صلاحية للوصول لهذه الصفحة.');
        }

        return $next($request);
    }
}
