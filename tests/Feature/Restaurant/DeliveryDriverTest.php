<?php

namespace Tests\Feature\Restaurant;

use App\Models\DeliveryDriver;
use App\Models\Restaurant;
use App\Models\RestaurantStaff;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryDriverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PermissionCatalog::syncDefaults();
    }

    public function test_owner_can_add_a_driver_with_vehicle_details(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->post('/restaurant/delivery-drivers', [
                'name' => 'كابتن سامي',
                'email' => 'samy.driver@example.com',
                'phone' => '01012345678',
                'password' => 'password1',
                'vehicle_type' => 'MOTORCYCLE',
                'vehicle_plate' => 'أ ب 1234',
            ])
            ->assertRedirect(route('restaurant.delivery-drivers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('delivery_drivers', [
            'restaurant_id' => $owner->restaurant->id,
            'name' => 'كابتن سامي',
            'phone' => '01012345678',
            'vehicle_type' => 'MOTORCYCLE',
            'vehicle_plate' => 'أ ب 1234',
            'is_active' => true,
            'availability_status' => 'AVAILABLE',
        ]);
    }

    public function test_owner_can_disable_a_driver_account(): void
    {
        $owner = $this->owner();
        $driver = $this->driver($owner->restaurant);

        $this->actingAs($owner)
            ->from('/restaurant/delivery-drivers')
            ->post("/restaurant/delivery-drivers/{$driver->id}/toggle")
            ->assertRedirect('/restaurant/delivery-drivers');

        $this->assertFalse($driver->fresh()->is_active);
        $this->assertFalse($driver->user->fresh()->is_active);
    }

    public function test_owner_cannot_toggle_another_restaurants_driver(): void
    {
        $owner = $this->owner();
        $other = $this->owner('other-restaurant');
        $driver = $this->driver($other->restaurant);

        $this->actingAs($owner)
            ->post("/restaurant/delivery-drivers/{$driver->id}/toggle")
            ->assertNotFound();

        $this->assertTrue($driver->fresh()->is_active);
    }

    public function test_staff_without_driver_permission_cannot_add_a_driver(): void
    {
        $staff = $this->owner('staff-restaurant', 'RESTAURANT_STAFF');

        $this->actingAs($staff)
            ->post('/restaurant/delivery-drivers', [
                'name' => 'كابتن',
                'email' => 'blocked.driver@example.com',
                'phone' => '01099998888',
                'password' => 'password1',
            ])
            ->assertForbidden();
    }

    private function owner(string $slug = 'drivers-restaurant', string $role = 'RESTAURANT_OWNER'): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        $restaurant = Restaurant::query()->create([
            'name' => 'مطعم الكباتن',
            'slug' => $slug,
            'address' => 'برج العرب',
            'status' => 'ACTIVE',
            'availability_status' => 'OPEN',
            'delivery_provider' => 'PLATFORM',
        ]);

        RestaurantStaff::query()->create([
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'role' => $role,
            'is_active' => true,
        ]);

        return $user->fresh();
    }

    private function driver(Restaurant $restaurant): DeliveryDriver
    {
        $user = User::factory()->create([
            'role' => 'DELIVERY_DRIVER',
            'phone' => '010'.random_int(10000000, 99999999),
            'is_active' => true,
        ]);
        $user->assignRole('DELIVERY_DRIVER');

        return DeliveryDriver::query()->create([
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'name' => 'كابتن محمود',
            'phone' => $user->phone,
            'is_active' => true,
            'availability_status' => 'AVAILABLE',
        ]);
    }
}
