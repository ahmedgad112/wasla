<?php

namespace App\Http\Middleware;

use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Share global data with every Inertia response.
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        $site = SiteSettings::shared();

        $permissions = [];
        $shellRestaurant = null;

        if ($user) {
            $permissions = cache()->remember(
                "user.{$user->id}.permissions",
                300,
                fn () => $user->getAllPermissions()->pluck('name')->values()->all()
            );

            if ($user->isRestaurantStaff()) {
                $shellRestaurant = cache()->remember(
                    "user.{$user->id}.shell_restaurant",
                    60,
                    function () use ($user) {
                        $restaurant = $user->restaurantStaff()
                            ->with('restaurant:id,name,status,logo,availability_status')
                            ->first()?->restaurant;

                        return $restaurant?->only('id', 'name', 'status', 'logo', 'availability_status');
                    }
                );
            }
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? $user->only('id', 'name', 'email', 'phone', 'role', 'is_active', 'avatar') : null,
                'permissions' => $permissions,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
            ],
            'app_name' => $site['app_name'],
            'app_slogan' => $site['app_tagline'],
            'support_phone' => $site['support_phone'],
            'site' => $site,
            'shell_restaurant' => $shellRestaurant,
            'impersonation' => $request->session()->has('impersonator_id')
                ? ['admin_name' => (string) $request->session()->get('impersonator_name', '')]
                : null,
        ]);
    }
}
