<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\RestaurantStaff;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RestaurantDeliveryProviderTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        Role::findOrCreate('SUPER_ADMIN', 'web');
        Role::findOrCreate('RESTAURANT_OWNER', 'web');

        $admin = User::factory()->create([
            'role' => 'SUPER_ADMIN',
            'is_active' => true,
        ]);
        $admin->assignRole('SUPER_ADMIN');

        return $admin;
    }

    /**
     * @return array{0: User, 1: Restaurant}
     */
    private function restaurantOwnerWithProvider(string $provider): array
    {
        Role::findOrCreate('RESTAURANT_OWNER', 'web');

        $owner = User::factory()->create([
            'role' => 'RESTAURANT_OWNER',
            'is_active' => true,
        ]);
        $owner->assignRole('RESTAURANT_OWNER');

        $restaurant = Restaurant::query()->create([
            'name' => 'مطعم التوصيل',
            'slug' => 'delivery-provider-test-'.uniqid(),
            'phone' => '01001112233',
            'address' => 'برج العرب',
            'status' => 'ACTIVE',
            'availability_status' => 'OPEN',
            'opening_time' => '08:00:00',
            'closing_time' => '23:00:00',
            'minimum_order_amount' => 20,
            'delivery_fee' => 10,
            'delivery_base_fee' => 10,
            'delivery_fee_per_km' => 5,
            'delivery_provider' => $provider,
            'delivery_enabled' => true,
            'commission_type' => 'PERCENTAGE',
            'commission_percentage' => 10,
            'monthly_subscription_fee' => 0,
            'student_discount_percentage' => 0,
        ]);

        RestaurantStaff::query()->create([
            'restaurant_id' => $restaurant->id,
            'user_id' => $owner->id,
            'role' => 'OWNER',
            'is_active' => true,
        ]);

        return [$owner, $restaurant];
    }

    public function test_admin_can_create_restaurant_with_platform_delivery(): void
    {
        SystemSetting::set('default_delivery_fee', '12.00', 'finance');
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post('/admin/restaurants', [
            'name' => 'مطعم المنصة',
            'phone' => '01000000011',
            'address' => 'برج العرب',
            'delivery_provider' => 'PLATFORM',
            'delivery_fee' => 18,
            'delivery_base_fee' => 15,
            'delivery_fee_per_km' => 3,
            'commission_rate' => 10,
            'owner_name' => 'مالك المنصة',
            'owner_email' => 'platform-owner@example.com',
            'owner_password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.restaurants.index'));
        $this->assertDatabaseHas('restaurants', [
            'name' => 'مطعم المنصة',
            'delivery_provider' => 'PLATFORM',
            'delivery_enabled' => 1,
            'delivery_fee' => 18,
        ]);
    }

    public function test_platform_delivery_restaurant_cannot_change_delivery_fees(): void
    {
        [$owner, $restaurant] = $this->restaurantOwnerWithProvider('PLATFORM');

        $this->actingAs($owner)
            ->post('/restaurant/settings', [
                'name' => $restaurant->name,
                'phone' => $restaurant->phone,
                'address' => $restaurant->address,
                'opening_time' => '09:00',
                'closing_time' => '22:00',
                'minimum_order_amount' => 20,
                'estimated_delivery_time' => 30,
                'delivery_fee' => 99,
                'delivery_base_fee' => 88,
                'delivery_fee_per_km' => 7,
                'delivery_enabled' => false,
            ])
            ->assertRedirect();

        $restaurant->refresh();

        $this->assertSame(10.0, (float) $restaurant->delivery_fee);
        $this->assertSame(10.0, (float) $restaurant->delivery_base_fee);
        $this->assertSame(5.0, (float) $restaurant->delivery_fee_per_km);
        $this->assertTrue($restaurant->delivery_enabled);
    }

    public function test_restaurant_delivery_owner_can_update_fees_and_availability(): void
    {
        [$owner, $restaurant] = $this->restaurantOwnerWithProvider('RESTAURANT');

        $this->actingAs($owner)
            ->post('/restaurant/settings', [
                'name' => $restaurant->name,
                'phone' => $restaurant->phone,
                'address' => $restaurant->address,
                'opening_time' => '09:00',
                'closing_time' => '22:00',
                'minimum_order_amount' => 20,
                'estimated_delivery_time' => 30,
                'delivery_fee' => 22,
                'delivery_base_fee' => 18,
                'delivery_fee_per_km' => 4,
                'delivery_enabled' => false,
            ])
            ->assertRedirect();

        $restaurant->refresh();

        $this->assertSame(22.0, (float) $restaurant->delivery_fee);
        $this->assertSame(18.0, (float) $restaurant->delivery_base_fee);
        $this->assertSame(4.0, (float) $restaurant->delivery_fee_per_km);
        $this->assertFalse($restaurant->delivery_enabled);
    }

    public function test_admin_can_create_pickup_only_restaurant(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post('/admin/restaurants', [
            'name' => 'مطعم الاستلام',
            'phone' => '01000000022',
            'address' => 'برج العرب',
            'delivery_provider' => 'PICKUP',
            'commission_rate' => 10,
            'owner_name' => 'مالك الاستلام',
            'owner_email' => 'pickup-owner@example.com',
            'owner_password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.restaurants.index'));
        $this->assertDatabaseHas('restaurants', [
            'name' => 'مطعم الاستلام',
            'delivery_provider' => 'PICKUP',
            'delivery_enabled' => 0,
            'delivery_fee' => 0,
        ]);
    }

    public function test_pickup_restaurant_cannot_change_delivery_settings(): void
    {
        [$owner, $restaurant] = $this->restaurantOwnerWithProvider('PICKUP');
        $restaurant->update([
            'delivery_enabled' => false,
            'delivery_fee' => 0,
            'delivery_base_fee' => 0,
            'delivery_fee_per_km' => 0,
        ]);

        $this->actingAs($owner)
            ->post('/restaurant/settings', [
                'name' => $restaurant->name,
                'phone' => $restaurant->phone,
                'address' => $restaurant->address,
                'opening_time' => '09:00',
                'closing_time' => '22:00',
                'minimum_order_amount' => 20,
                'estimated_delivery_time' => 30,
                'delivery_fee' => 50,
                'delivery_base_fee' => 40,
                'delivery_fee_per_km' => 9,
                'delivery_enabled' => true,
            ])
            ->assertRedirect();

        $restaurant->refresh();

        $this->assertSame(0.0, (float) $restaurant->delivery_fee);
        $this->assertFalse($restaurant->delivery_enabled);
        $this->assertTrue($restaurant->isPickupOnly());
    }
}
