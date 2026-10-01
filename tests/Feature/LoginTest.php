<?php

namespace Tests\Feature;

use App\Models\DeliveryDriver;
use App\Models\Restaurant;
use App\Models\RestaurantStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_shared_for_every_account(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/Login'));
    }

    #[DataProvider('portalLoginPages')]
    public function test_old_portal_login_pages_redirect_to_the_shared_login(string $path): void
    {
        $this->get($path)->assertRedirect('/login');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function portalLoginPages(): array
    {
        return [
            'admin' => ['/admin/login'],
            'restaurant' => ['/restaurant/login'],
            'delivery' => ['/delivery/login'],
        ];
    }

    #[DataProvider('accountDashboards')]
    public function test_login_opens_the_dashboard_for_the_account_role(string $role, string $routeName): void
    {
        User::factory()->create([
            'email' => 'account@example.com',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);

        $this->post('/login', [
            'email' => 'account@example.com',
            'password' => 'password',
        ])->assertRedirect(route($routeName));

        $this->assertAuthenticated();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function accountDashboards(): array
    {
        return [
            'customer' => ['CUSTOMER', 'customer.dashboard'],
            'super admin' => ['SUPER_ADMIN', 'admin.dashboard'],
            'admin' => ['ADMIN', 'admin.dashboard'],
            'platform staff' => ['PLATFORM_STAFF', 'admin.dashboard'],
        ];
    }

    public function test_restaurant_owner_login_opens_the_restaurant_dashboard(): void
    {
        $owner = $this->restaurantUser('RESTAURANT_OWNER', 'owner@example.com');

        $this->post('/login', [
            'email' => $owner->email,
            'password' => 'password',
        ])->assertRedirect(route('restaurant.dashboard'));

        $this->assertAuthenticatedAs($owner);
    }

    public function test_restaurant_staff_login_opens_the_restaurant_dashboard(): void
    {
        $staff = $this->restaurantUser('RESTAURANT_STAFF', 'staff@example.com');

        $this->post('/login', [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertRedirect(route('restaurant.dashboard'));

        $this->assertAuthenticatedAs($staff);
    }

    public function test_delivery_driver_login_opens_the_delivery_dashboard(): void
    {
        $driver = $this->driver('driver@example.com', true);

        $this->post('/login', [
            'email' => $driver->email,
            'password' => 'password',
        ])->assertRedirect(route('delivery.dashboard'));

        $this->assertAuthenticatedAs($driver);
    }

    public function test_login_accepts_a_phone_number_and_opens_that_dashboard(): void
    {
        User::factory()->create([
            'email' => 'phone-admin@example.com',
            'phone' => '01012345678',
            'password' => 'password',
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->post('/login', [
            'email' => '01012345678',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_restaurant_account_without_a_restaurant_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'orphan@example.com',
            'password' => 'password',
            'role' => 'RESTAURANT_OWNER',
            'is_active' => true,
        ]);

        $this->from('/login')
            ->post('/login', [
                'email' => 'orphan@example.com',
                'password' => 'password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_account_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'off@example.com',
            'password' => 'password',
            'role' => 'ADMIN',
            'is_active' => false,
        ]);

        $this->from('/login')
            ->post('/login', [
                'email' => 'off@example.com',
                'password' => 'password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_delivery_driver_is_rejected(): void
    {
        $driver = $this->driver('inactive-driver@example.com', false);

        $this->from('/login')
            ->post('/login', [
                'email' => $driver->email,
                'password' => 'password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->from('/login')
            ->post('/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_admin_visiting_login_is_sent_to_the_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/login')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_logout_returns_to_the_shared_login(): void
    {
        $admin = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    private function restaurantUser(string $role, string $email): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);

        $restaurant = Restaurant::query()->create([
            'name' => 'مطعم وصلة',
            'slug' => 'wasla-'.strtolower($role),
            'address' => 'برج العرب',
            'status' => 'ACTIVE',
            'availability_status' => 'OPEN',
        ]);

        RestaurantStaff::query()->create([
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'role' => $role,
            'is_active' => true,
        ]);

        return $user;
    }

    private function driver(string $email, bool $active): User
    {
        $user = User::factory()->create([
            'email' => $email,
            'password' => 'password',
            'role' => 'DELIVERY_DRIVER',
            'is_active' => true,
        ]);

        $restaurant = Restaurant::query()->create([
            'name' => 'مطعم التوصيل',
            'slug' => 'delivery-'.str_replace(['@', '.'], '-', $email),
            'address' => 'برج العرب',
            'status' => 'ACTIVE',
            'availability_status' => 'OPEN',
        ]);

        DeliveryDriver::query()->create([
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'name' => 'كابتن',
            'phone' => '01099887766',
            'is_active' => $active,
            'availability_status' => 'AVAILABLE',
        ]);

        return $user;
    }
}
