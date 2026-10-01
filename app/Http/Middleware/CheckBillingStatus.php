<?php

namespace App\Http\Middleware;

use App\Models\Restaurant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBillingStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->isRestaurantStaff()) {
            $restaurant = $user->restaurantStaff()->first()?->restaurant;
            $this->suspendIfBillingExpired($restaurant);

            if ($restaurant && $restaurant->status === 'SUSPENDED') {
                $allowedRoutes = ['restaurant.billing', 'restaurant.logout', 'logout'];
                $currentRoute = $request->route()?->getName();

                if (! in_array($currentRoute, $allowedRoutes, true) && ! $request->is('logout*') && ! $request->is('restaurant/billing*')) {
                    return redirect()->route('restaurant.billing')->with(
                        'error',
                        'حساب المطعم موقوف مؤقتاً لعدم سداد المستحقات. يرجى مراجعة الفواتير والتواصل مع الدعم الفني لإعادة التفعيل.'
                    );
                }
            }
        }

        if ($user->isDeliveryDriver()) {
            $restaurant = $user->deliveryDriver?->restaurant;
            $this->suspendIfBillingExpired($restaurant);

            if ($restaurant && $restaurant->status === 'SUSPENDED') {
                $allowedRoutes = ['delivery.suspended', 'delivery.logout', 'logout'];
                $currentRoute = $request->route()?->getName();

                if (! in_array($currentRoute, $allowedRoutes, true) && ! $request->is('logout*') && ! $request->is('delivery/suspended*')) {
                    return redirect()->route('delivery.suspended');
                }
            }
        }

        return $next($request);
    }

    private function suspendIfBillingExpired(?Restaurant $restaurant): void
    {
        if (! $restaurant || $restaurant->status !== 'ACTIVE' || ! $restaurant->billingAccessExpired()) {
            return;
        }

        $restaurant->update([
            'status' => 'SUSPENDED',
            'billing_suspended_at' => now(),
            'suspension_reason' => 'تجاوز موعد الاستحقاق دون تسجيل السداد',
        ]);
    }
}
