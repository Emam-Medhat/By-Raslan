<?php

namespace App\Modules\Identity\Http\Middleware;

use App\Modules\Identity\Support\AccessRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNavAccess
{
    /** Route name prefix/pattern to nav permission. Most specific first. */
    private static function map(): array
    {
        return [
            'statistics.*' => 'nav.statistics',
            'pos.*' => 'nav.pos',
            'sales.*' => 'nav.sales',
            'returns.*' => 'nav.returns',
            'reservations.*' => 'nav.reservations',
            'order-collections*' => 'nav.collections',
            'orders.collect' => 'nav.collections',
            'orders.*' => 'nav.orders',
            'prices.*' => 'nav.prices',
            'items.*' => 'nav.items',
            'stock.dispose*' => 'nav.dispose',
            'stock.adjust*' => 'nav.stock',
            'stock.*' => 'nav.stock',
            'stock-counts.*' => 'nav.stock_counts',
            'boxes.*' => 'nav.boxes',
            'transfers.*' => 'nav.transfers',
            'purchasing.suppliers.*' => 'nav.suppliers',
            'purchase-returns.*' => 'nav.purchase_returns',
            'purchasing.specs.*' => 'nav.specs',
            'purchasing.receipts.*' => 'nav.purchasing',
            'purchasing.*' => 'nav.purchasing',
            'overrides.*' => 'nav.overrides',
            'production.routes*' => 'nav.production_routes',
            'production.station*' => 'nav.production_station',
            'production.*' => 'nav.production',
            'pack.*' => 'nav.pack',
            'trips.*' => 'nav.trips',
            'accounts.suppliers.*' => 'nav.accounts',
            'accounts.*' => 'nav.accounts',
            'cash-transfers.*' => 'nav.cash_transfers',
            'expenses.*' => 'nav.expenses',
            'shift-expenses.*' => 'nav.shift_expenses',
            'shifts.*' => 'nav.shifts',
            'locations.*' => 'nav.locations',
            'users.*' => 'nav.users',
            'roles.*' => 'nav.roles',
            'notifications.*' => 'nav.notifications',
            'audit.*' => 'nav.audit',
            'settings.*' => 'nav.settings',
            'dashboard*' => 'nav.dashboard',
        ];
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->isOwner()) return $next($request);

        $routeName = (string) $request->route()?->getName();
        if ($routeName === '') return $next($request);

        $required = null;
        foreach (self::map() as $pattern => $perm) {
            if (\Illuminate\Support\Str::is($pattern, $routeName)) { $required = $perm; break; }
        }
        if (! $required) return $next($request);

        // Allow if user has nav permission. Also allow legacy users who have no nav perms at all
        // (migration safety) only if they have the implied business perm? No — user asked for strict.
        // Legacy compatibility: users configured before nav.* permissions keep old behavior
        // until an admin assigns sidebar links. Once any nav.* permission is assigned, enforce strictly.
        $hasNavPerms = $user->permissions()->where('permission_id', 'like', 'nav.%')->exists()
            || $user->roles()->whereHas('permissions', fn($q) => $q->where('permissions.id', 'like', 'nav.%'))->exists();
        if (! $hasNavPerms) return $next($request);

        if ($user->hasPermission($required)) return $next($request);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'ليس لديك صلاحية للوصول لهذه الصفحة.'], 403);
        }
        abort(403, 'ليس لديك صلاحية للوصول لهذه الصفحة.');
    }
}
