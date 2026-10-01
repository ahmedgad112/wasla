<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\RestaurantStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RestaurantAvailabilityStatusesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_poll_restaurant_availability_statuses(): void
    {
        $open = Restaurant::query()->create([
            'name' => 'مطعم مفتوح',
            'slug' => 'open-restaurant',
            'phone' => '01001112233',
            'address' => 'برج العرب',
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

        $busy = Restaurant::query()->create([
            'name' => 'مطعم مشغول',
            'slug' => 'busy-restaurant',
            'phone' => '01001112234',
            'address' => 'برج العرب',
            'status' => 'ACTIVE',
            'availability_status' => 'BUSY',
            'opening_time' => '08:00:00',
            'closing_time' => '23:00:00',
            'minimum_order_amount' => 20,
            'delivery_fee' => 10,
            'commission_type' => 'PERCENTAGE',
            'commission_percentage' => 10,
            'monthly_subscription_fee' => 0,
            'student_discount_percentage' => 0,
        ]);

        Restaurant::query()->create([
            'name' => 'مطعم غير ظاهر',
            'slug' => 'inactive-restaurant',
            'phone' => '01001112235',
            'address' => 'برج العرب',
            'status' => 'INACTIVE',
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

        $response = $this->getJson(route('restaurants.availability'));

        $response->assertOk()
            ->assertJsonCount(2, 'restaurants')
            ->assertJsonFragment([
                'id' => $open->id,
                'status' => 'ACTIVE',
                'availability_status' => 'OPEN',
            ])
            ->assertJsonFragment([
                'id' => $busy->id,
                'status' => 'ACTIVE',
                'availability_status' => 'BUSY',
            ]);
    }

    public function test_availability_poll_reflects_status_changes_immediately(): void
    {
        $restaurant = Restaurant::query()->create([
            'name' => 'مطعم التحديث',
            'slug' => 'live-update-restaurant',
            'phone' => '01001112236',
            'address' => 'برج العرب',
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

        $this->getJson(route('restaurants.availability'))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $restaurant->id,
                'availability_status' => 'OPEN',
            ]);

        Role::findOrCreate('RESTAURANT_OWNER', 'web');
        $owner = User::factory()->create([
            'role' => 'RESTAURANT_OWNER',
            'is_active' => true,
        ]);
        $owner->assignRole('RESTAURANT_OWNER');
        RestaurantStaff::query()->create([
            'restaurant_id' => $restaurant->id,
            'user_id' => $owner->id,
            'role' => 'RESTAURANT_OWNER',
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post('/restaurant/settings/availability', [
                'availability_status' => 'CLOSED',
            ])
            ->assertRedirect();

        $this->getJson(route('restaurants.availability'))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $restaurant->id,
                'availability_status' => 'CLOSED',
            ]);
    }
}
