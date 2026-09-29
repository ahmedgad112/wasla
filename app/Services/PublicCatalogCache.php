<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class PublicCatalogCache
{
    public const FEATURED_RESTAURANTS = 'public.featured_restaurants';

    public const ACTIVE_OFFERS = 'public.active_offers';

    public const STATS = 'public.stats';

    public const HOME_LEADERBOARD = 'public.home_leaderboard';

    public const AVAILABILITY = 'public.availability_statuses';

    public static function restaurantKey(string $slug): string
    {
        return "public.restaurant.{$slug}";
    }

    public static function forgetListing(): void
    {
        Cache::forget(self::FEATURED_RESTAURANTS);
        Cache::forget(self::ACTIVE_OFFERS);
        Cache::forget(self::STATS);
        Cache::forget(self::HOME_LEADERBOARD);
        Cache::forget(self::AVAILABILITY);
        Cache::forget('landing_page_payload');
        Cache::forget('public.cms_settings');
    }

    public static function forgetRestaurant(?string $slug): void
    {
        if ($slug) {
            Cache::forget(self::restaurantKey($slug));
        }

        Cache::forget(self::FEATURED_RESTAURANTS);
        Cache::forget(self::ACTIVE_OFFERS);
        Cache::forget(self::STATS);
        Cache::forget(self::AVAILABILITY);
    }

    public static function forgetAvailability(): void
    {
        Cache::forget(self::AVAILABILITY);
    }
}
