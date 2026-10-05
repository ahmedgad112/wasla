<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Offer;
use App\Models\Restaurant;
use App\Models\RestaurantStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
                ->where('restaurant.slug', 'test-restaurant-actions')
                ->where('stats.total_orders', 0)
                ->has('recentOrders')
                ->has('accounts')
                ->where('restaurant.commission_type', 'PERCENTAGE')
                ->where('billing.access_expired', false)
                ->has('invoices'));
    }

    public function test_restaurant_details_include_the_subscription_profile(): void
    {
        $admin = $this->adminUser();
        $restaurant = $this->restaurant([
            'slug' => 'subscription-restaurant-profile',
            'phone' => '01005554433',
            'commission_type' => 'SUBSCRIPTION',
            'commission_percentage' => 0,
            'monthly_subscription_fee' => 900,
            'billing_cycle' => 'QUARTERLY',
            'grace_period_days' => 7,
            'subscription_starts_at' => '2026-10-01',
            'subscription_ends_at' => '2026-12-31',
            'payment_due_date' => '2027-01-07',
            'delivery_provider' => 'PLATFORM',
        ]);

        $this->actingAs($admin)
            ->get("/admin/restaurants/{$restaurant->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('restaurant.commission_type', 'SUBSCRIPTION')
                ->where('restaurant.billing_cycle', 'QUARTERLY')
                ->where('restaurant.delivery_provider', 'PLATFORM')
                ->where('restaurant.grace_period_days', 7)
                ->where('billing.suspended_for_billing', false)
                ->where('billing.access_expired', false));
    }

    public function test_admin_can_change_a_restaurant_login_from_its_details(): void
    {
        $admin = $this->adminUser();
        $restaurant = $this->restaurant();
        $owner = User::factory()->create([
            'name' => 'مالك المطعم',
            'email' => 'owner@restaurant.test',
            'role' => 'RESTAURANT_OWNER',
            'password' => 'old-password',
        ]);
        RestaurantStaff::query()->create([
            'restaurant_id' => $restaurant->id,
            'user_id' => $owner->id,
            'role' => 'OWNER',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get("/admin/restaurants/{$restaurant->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('accounts.0.email', 'owner@restaurant.test')
                ->where('restaurant.owner.email', 'owner@restaurant.test'));

        $this->actingAs($admin)
            ->put("/admin/restaurants/{$restaurant->id}/accounts/{$owner->id}", [
                'name' => 'المالك الجديد',
                'email' => 'new-owner@restaurant.test',
                'password' => 'new-password',
            ])
            ->assertRedirect(route('admin.restaurants.show', $restaurant->id))
            ->assertSessionHas('success');

        $owner->refresh();
        $this->assertSame('المالك الجديد', $owner->name);
        $this->assertSame('new-owner@restaurant.test', $owner->email);
        $this->assertTrue(Hash::check('new-password', $owner->password));
    }

    public function test_blank_password_keeps_the_restaurant_login_password(): void
    {
        $admin = $this->adminUser();
        $restaurant = $this->restaurant();
        $owner = User::factory()->create([
            'email' => 'keep@restaurant.test',
            'role' => 'RESTAURANT_OWNER',
            'password' => 'kept-password',
        ]);
        RestaurantStaff::query()->create([
            'restaurant_id' => $restaurant->id,
            'user_id' => $owner->id,
            'role' => 'OWNER',
        ]);

        $this->actingAs($admin)
            ->put("/admin/restaurants/{$restaurant->id}/accounts/{$owner->id}", [
                'name' => $owner->name,
                'email' => 'keep@restaurant.test',
                'password' => '',
            ])
            ->assertRedirect(route('admin.restaurants.show', $restaurant->id));

        $this->assertTrue(Hash::check('kept-password', $owner->fresh()->password));
    }

    public function test_admin_cannot_change_a_login_that_belongs_to_another_restaurant(): void
    {
        $admin = $this->adminUser();
        $restaurant = $this->restaurant();
        $other = $this->restaurant(['slug' => 'other-restaurant-actions', 'phone' => '01009998877']);
        $owner = User::factory()->create([
            'email' => 'other@restaurant.test',
            'role' => 'RESTAURANT_OWNER',
        ]);
        RestaurantStaff::query()->create([
            'restaurant_id' => $other->id,
            'user_id' => $owner->id,
            'role' => 'OWNER',
        ]);

        $this->actingAs($admin)
            ->put("/admin/restaurants/{$restaurant->id}/accounts/{$owner->id}", [
                'name' => 'اختراق',
                'email' => 'hacked@restaurant.test',
                'password' => 'new-password',
            ])
            ->assertNotFound();

        $this->assertSame('other@restaurant.test', $owner->fresh()->email);
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

    public function test_deleting_a_restaurant_removes_its_offers_and_menu_from_customers(): void
    {
        $admin = $this->adminUser();
        $restaurant = $this->restaurant();
        $category = Category::query()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'فطار',
            'slug' => 'breakfast-delete',
        ]);
        $item = MenuItem::query()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => 'سندوتش محذوف',
            'price' => 40,
            'is_available' => true,
        ]);
        Offer::query()->create([
            'restaurant_id' => $restaurant->id,
            'menu_item_id' => $item->id,
            'title' => 'عرض المطعم المحذوف',
            'original_price' => 40,
            'discount_price' => 25,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete("/admin/restaurants/{$restaurant->id}")
            ->assertRedirect(route('admin.restaurants.index'));

        $this->assertDatabaseMissing('offers', ['title' => 'عرض المطعم المحذوف']);
        $this->assertDatabaseMissing('menu_items', ['id' => $item->id]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);

        $this->get('/offers')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Offers')
                ->where('offers.data', []));

        $this->get("/restaurants/{$restaurant->slug}")
            ->assertRedirect(route('restaurants'));
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
