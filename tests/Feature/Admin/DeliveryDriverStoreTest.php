<?php

namespace Tests\Feature\Admin;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeliveryDriverStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_platform_driver_without_a_restaurant(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post('/admin/delivery-drivers', [
                'name' => 'كابتن الموقع',
                'email' => 'site.driver@example.com',
                'phone' => '01012121212',
                'password' => 'password1',
                'affiliation' => 'platform',
                'vehicle_type' => 'MOTORCYCLE',
            ])
            ->assertRedirect(route('admin.delivery-drivers.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('delivery_drivers', [
            'name' => 'كابتن الموقع',
            'restaurant_id' => null,
            'phone' => '01012121212',
        ]);
    }

    public function test_a_restaurant_driver_requires_a_restaurant(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from('/admin/delivery-drivers/create')
            ->post('/admin/delivery-drivers', [
                'name' => 'كابتن المطعم',
                'email' => 'restaurant.driver@example.com',
                'phone' => '01013131313',
                'password' => 'password1',
                'affiliation' => 'restaurant',
            ])
            ->assertRedirect('/admin/delivery-drivers/create')
            ->assertSessionHasErrors('restaurant_id');

        $this->assertDatabaseMissing('delivery_drivers', [
            'name' => 'كابتن المطعم',
        ]);
    }

    public function test_admin_can_attach_a_driver_to_one_restaurant(): void
    {
        $admin = $this->admin();
        $restaurant = Restaurant::query()->create([
            'name' => 'مطعم الكابتن',
            'slug' => 'captain-restaurant',
            'address' => 'برج العرب',
            'status' => 'ACTIVE',
            'availability_status' => 'OPEN',
            'delivery_provider' => 'RESTAURANT',
        ]);

        $this->actingAs($admin)
            ->post('/admin/delivery-drivers', [
                'name' => 'كابتن المطعم',
                'email' => 'owned.driver@example.com',
                'phone' => '01014141414',
                'password' => 'password1',
                'affiliation' => 'restaurant',
                'restaurant_id' => $restaurant->id,
            ])
            ->assertRedirect(route('admin.delivery-drivers.index'));

        $this->assertDatabaseHas('delivery_drivers', [
            'name' => 'كابتن المطعم',
            'restaurant_id' => $restaurant->id,
        ]);
    }

    private function admin(): User
    {
        Role::findOrCreate('SUPER_ADMIN', 'web');
        Role::findOrCreate('DELIVERY_DRIVER', 'web');

        $admin = User::factory()->create([
            'role' => 'SUPER_ADMIN',
            'is_active' => true,
        ]);
        $admin->assignRole('SUPER_ADMIN');

        return $admin;
    }
}
