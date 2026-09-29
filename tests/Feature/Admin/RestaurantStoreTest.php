<?php

namespace Tests\Feature\Admin;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RestaurantStoreTest extends TestCase
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

    public function test_admin_can_create_restaurant_with_owner_account(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post('/admin/restaurants', [
            'name' => 'مطعم الاختبار',
            'description' => 'وصف تجريبي',
            'phone' => '01000000001',
            'email' => 'test-restaurant@example.com',
            'address' => 'برج العرب',
            'opening_time' => '08:00',
            'closing_time' => '23:00',
            'delivery_fee' => '12.50',
            'delivery_provider' => 'RESTAURANT',
            'minimum_order_amount' => '25.00',
            'estimated_delivery_time' => 35,
            'commission_rate' => 12,
            'owner_name' => 'مالك الاختبار',
            'owner_email' => 'owner-test@example.com',
            'owner_password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.restaurants.index'));

        $this->assertDatabaseHas('restaurants', [
            'name' => 'مطعم الاختبار',
            'phone' => '01000000001',
            'status' => 'ACTIVE',
            'availability_status' => 'CLOSED',
            'delivery_provider' => 'RESTAURANT',
            'commission_percentage' => 12,
            'commission_type' => 'PERCENTAGE',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'owner-test@example.com',
            'role' => 'RESTAURANT_OWNER',
        ]);

        $restaurant = Restaurant::where('name', 'مطعم الاختبار')->first();
        $owner = User::where('email', 'owner-test@example.com')->first();

        $this->assertNotNull($restaurant);
        $this->assertNotNull($owner);
        $this->assertDatabaseHas('restaurant_staff', [
            'restaurant_id' => $restaurant->id,
            'user_id' => $owner->id,
            'role' => 'OWNER',
        ]);
    }

    public function test_create_restaurant_requires_owner_fields(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->from('/admin/restaurants/create')->post('/admin/restaurants', [
            'name' => 'مطعم ناقص',
            'phone' => '01000000002',
            'address' => 'برج العرب',
        ]);

        $response->assertRedirect('/admin/restaurants/create');
        $response->assertSessionHasErrors(['owner_name', 'owner_email', 'owner_password']);
        $this->assertDatabaseMissing('restaurants', ['name' => 'مطعم ناقص']);
    }
}
