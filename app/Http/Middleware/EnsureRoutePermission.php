<?php

namespace App\Http\Middleware;

use App\Support\PermissionCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoutePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $permission = PermissionCatalog::forRoute($request->route()?->getName(), $request->method());

        if (
            $user
            && $permission === 'restaurant.billing'
            && $user->isRestaurantStaff()
            && $user->restaurant?->status === 'SUSPENDED'
        ) {
            return $next($request);
        }

        if ($user && $permission === PermissionCatalog::UNMAPPED && ! $user->isSuperAdmin()) {
            abort(403);
        }

        if ($user && $permission !== null && $permission !== PermissionCatalog::UNMAPPED && ! $user->can($permission)) {
            abort(403);
        }

        return $next($request);
    }
}
