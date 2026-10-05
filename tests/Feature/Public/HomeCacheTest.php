<?php

namespace Tests\Feature\Public;

use App\Models\Restaurant;
use App\Services\PublicCatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomeCacheTest extends TestCase
{
    use RefreshDatabase;

    private function seedRestaurant(): Restaurant
    {
        return Restaurant::query()->create([
            'name' => 'مطعم السرعة',
            'slug' => 'speed-restaurant',
            'phone' => '01001112233',
            'address' => 'برج العرب',
            'latitude' => 30.8756,
            'longitude' => 29.5842,
            'status' => 'ACTIVE',
            'availability_status' => 'OPEN',
            'opening_time' => '08:00:00',
            'closing_time' => '23:00:00',
            'minimum_order_amount' => 20,
            'delivery_fee' => 10,
            'commission_type' => 'PERCENTAGE',
            'commission_percentage' => 10,
            'monthly_subscription_fee' => 0,
            'student_discount_percentage' => 0,
        ]);
    }

    public function test_home_page_uses_cached_restaurant_listing(): void
    {
        $this->seedRestaurant();

        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page
            ->where('restaurants.0.latitude', 30.8756)
            ->where('restaurants.0.longitude', 29.5842)
        );
        $this->assertTrue(Cache::has(PublicCatalogCache::FEATURED_RESTAURANTS));

        DB::enableQueryLog();
        $this->get('/')->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query')->implode(' ');

        $this->assertStringNotContainsString('from "restaurants"', strtolower($queries));
    }

    public function test_availability_endpoint_is_cached_briefly(): void
    {
        $restaurant = $this->seedRestaurant();

        $this->getJson('/restaurants/availability-statuses')
            ->assertOk()
            ->assertJsonPath('restaurants.0.id', $restaurant->id)
            ->assertHeader('Cache-Control', 'max-age=5, public');

        $this->assertTrue(Cache::has(PublicCatalogCache::AVAILABILITY));

        $restaurant->update(['availability_status' => 'BUSY']);

        // Still served from cache until TTL expires / invalidated.
        $this->getJson('/restaurants/availability-statuses')
            ->assertOk()
            ->assertJsonPath('restaurants.0.availability_status', 'OPEN');

        PublicCatalogCache::forgetAvailability();

        $this->getJson('/restaurants/availability-statuses')
            ->assertOk()
            ->assertJsonPath('restaurants.0.availability_status', 'BUSY');
    }

    public function test_restaurant_details_are_cached_and_invalidated_on_settings_change(): void
    {
        $restaurant = $this->seedRestaurant();

        $this->get("/restaurants/{$restaurant->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/RestaurantDetails')
                ->where('restaurant.name', 'مطعم السرعة')
                ->where('restaurant.slug', $restaurant->slug)
            );

        $this->assertTrue(Cache::has(PublicCatalogCache::restaurantKey($restaurant->slug)));
        $this->assertIsArray(Cache::get(PublicCatalogCache::restaurantKey($restaurant->slug)));

        $this->get("/restaurants/{$restaurant->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('restaurant.name', 'مطعم السرعة')
            );

        PublicCatalogCache::forgetRestaurant($restaurant->slug);
        $this->assertFalse(Cache::has(PublicCatalogCache::restaurantKey($restaurant->slug)));
    }
}
