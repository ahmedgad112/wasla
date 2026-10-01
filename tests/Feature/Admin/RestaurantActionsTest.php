<?php

namespace Tests\Feature\Admin;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RestaurantActionsTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        Role::findOrCreate('SUPER_ADMIN', 'web');

        $admin = User::factory()->create([
            'role' => 'SUPER_ADMIN',
            'is_active' => true,
        ]);
        $admin->assignRole('SUPER_ADMIN');

        return $admin;
    }

    private function restaurant(array $overrides = []): Restaurant
    {
        return Restaurant::query()->create(array_merge([
            'name' => 'مطعم الاختبار',
            'slug' => 'test-restaurant-actions',
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
        ], $overrides));
    }

    public function test_admin_can_view_restaurant_details(): void
    {
        $admin = $this->adminUser();
        $restaurant = $this->restaurant();

        $this->actingAs($admin)
            ->get("/admin/restaurants/{$restaurant->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Restaurants/Show')
                ->where('restaurant.id', $restaurant->id)
                ->where('restaurant.slug', 'test-restaurant-actions'));
    }

    public function test_admin_can_open_restaurant_edit_page(): void
    {
        $admin = $this->adminUser();
        $restaurant = $this->restaurant();

        $this->actingAs($admin)
            ->get("/admin/restaurants/{$restaurant->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Restaurants/Edit')
                ->where('restaurant.id', $restaurant->id));
    }

    public function test_admin_can_suspend_and_activate_restaurant(): void
    {
        $admin = $this->adminUser();
        $restaurant = $this->restaurant();

        $this->actingAs($admin)
            ->post("/admin/restaurants/{$restaurant->id}/suspend")
            ->assertRedirect();

        $this->assertSame('SUSPENDED', $restaurant->fresh()->status);

        $this->actingAs($admin)
            ->post("/admin/restaurants/{$restaurant->id}/activate")
            ->assertRedirect();

        $this->assertSame('ACTIVE', $restaurant->fresh()->status);
    }

    public function test_admin_can_delete_a_partner_restaurant(): void
    {
        $admin = $this->adminUser();
        $restaurant = $this->restaurant();

        $this->actingAs($admin)
            ->delete("/admin/restaurants/{$restaurant->id}")
            ->assertRedirect(route('admin.restaurants.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted($restaurant);
    }

    public function test_deleting_a_missing_restaurant_returns_to_the_list(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->post('/admin/restaurants/99999/delete')
            ->assertRedirect(route('admin.restaurants.index'))
            ->assertSessionHas('error');
    }

    public function test_missing_public_restaurant_returns_to_the_list(): void
    {
        $this->get('/restaurants/missing-restaurant')
            ->assertRedirect(route('restaurants'))
            ->assertSessionHas('error');
    }

    public function test_legacy_singular_restaurant_path_is_not_used_for_public_storefront(): void
    {
        $restaurant = $this->restaurant();

        $this->get("/restaurant/{$restaurant->slug}")->assertNotFound();
        $this->get("/restaurants/{$restaurant->slug}")->assertOk();
    }
}
