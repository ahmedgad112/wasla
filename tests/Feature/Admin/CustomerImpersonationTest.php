<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'SUPER_ADMIN',
            'is_active' => true,
            'name' => 'مدير المنصة',
        ]);
    }

    private function customerUser(bool $active = true): User
    {
        $user = User::factory()->create([
            'role' => 'CUSTOMER',
            'is_active' => $active,
            'name' => 'عميل تجريبي',
        ]);

        Customer::query()->create([
            'user_id' => $user->id,
        ]);

        Role::findOrCreate('CUSTOMER', 'web');
        $user->assignRole('CUSTOMER');

        return $user;
    }

    public function test_admin_can_open_the_customer_account_and_return(): void
    {
        $admin = $this->admin();
        $customerUser = $this->customerUser();
        $customer = $customerUser->customer;

        $this->actingAs($admin)
            ->post(route('admin.customers.login-as', $customer->id))
            ->assertRedirect(route('customer.dashboard'))
            ->assertSessionHas('impersonator_id', $admin->id);

        $this->assertAuthenticatedAs($customerUser);

        $this->get(route('customer.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('auth.user.id', $customerUser->id)
                ->where('impersonation.admin_name', 'مدير المنصة')
            );

        $this->post(route('impersonation.leave'))
            ->assertRedirect(route('admin.customers.index'))
            ->assertSessionHas('success', 'رجعت لحساب الإدارة.')
            ->assertSessionMissing('impersonator_id');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_logout_while_impersonating_returns_to_the_admin_account(): void
    {
        $admin = $this->admin();
        $customerUser = $this->customerUser();

        $this->actingAs($admin)
            ->post(route('admin.customers.login-as', $customerUser->customer->id));

        $this->assertAuthenticatedAs($customerUser);

        $this->post(route('logout'))
            ->assertRedirect(route('admin.customers.index'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_staff_without_customer_manage_permission_cannot_login_as_a_customer(): void
    {
        Permission::findOrCreate('customers.view', 'web');
        Role::findOrCreate('PLATFORM_STAFF', 'web')->syncPermissions(['customers.view']);

        $staff = User::factory()->create([
            'role' => 'PLATFORM_STAFF',
            'is_active' => true,
        ]);
        $staff->assignRole('PLATFORM_STAFF');

        $customerUser = $this->customerUser();

        $this->actingAs($staff)
            ->post(route('admin.customers.login-as', $customerUser->customer->id))
            ->assertForbidden();

        $this->assertAuthenticatedAs($staff);
    }

    public function test_admin_cannot_login_as_an_inactive_customer(): void
    {
        $admin = $this->admin();
        $customerUser = $this->customerUser(active: false);

        $this->actingAs($admin)
            ->from(route('admin.customers.index'))
            ->post(route('admin.customers.login-as', $customerUser->customer->id))
            ->assertRedirect(route('admin.customers.index'))
            ->assertSessionHas('error', 'لا يمكن تسجيل الدخول بهذا الحساب.');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $customerUser = $this->customerUser();

        $this->post(route('admin.customers.login-as', $customerUser->customer->id))
            ->assertRedirect(route('login'));
    }
}
