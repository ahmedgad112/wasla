<?php

use App\Http\Middleware\CheckBillingStatus;
use App\Http\Middleware\EnsurePortalRole;
use App\Http\Middleware\EnsureRoutePermission;
use App\Http\Middleware\HandleInertiaRequests;
use App\Services\AuthService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Inertia middleware — shares auth/flash data with every React page
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // Portal role enforcement — usage: ->middleware('portal:ADMIN')
        $middleware->alias([
            'portal' => EnsurePortalRole::class,
            'billing.check' => CheckBillingStatus::class,
            'route.permission' => EnsureRoutePermission::class,
        ]);

        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();

            if ($user === null) {
                return route('home');
            }

            return route(app(AuthService::class)->dashboardRouteName($user));
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
