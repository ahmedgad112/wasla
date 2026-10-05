<?php

namespace App\Http\Middleware;

use App\Support\SiteSettings;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureSiteIsAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isOperationalPath($request) || ! SiteSettings::flag('maintenance_mode')) {
            return $next($request);
        }

        return Inertia::render('Public/Maintenance', [
            'appName' => SiteSettings::shared()['app_name'] ?? 'وصلة',
            'supportPhone' => SiteSettings::shared()['support_phone'] ?? '',
        ])->toResponse($request)->setStatusCode(503);
    }

    private function isOperationalPath(Request $request): bool
    {
        return $request->is(
            'admin',
            'admin/*',
            'restaurant',
            'restaurant/*',
            'delivery',
            'delivery/*',
            'login',
            'logout',
            'up',
            'impersonation',
            'impersonation/*',
            '_boost',
            '_boost/*',
        );
    }
}
