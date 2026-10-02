<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerAccountStatusTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'SUPER_ADMIN',
            'is_active' => true,
        ]);
    }

    private function customerUser(): User
    {
        $user = User::factory()->create([
            'role' => 'CUSTOMER',
            'is_active' => true,
            'email' => 'customer-status@example.com',
            'password' => 'password',
        ]);

        Customer::query()->create([
            'user_id' => $user->id,
        ]);

        return $user;
    }

    public function test_admin_can_stop_and_restore_a_customer_account(): void
    {
        $admin = $this->admin();
        $customerUser = $this->customerUser();

        $this->actingAs($admin)
            ->from(route('admin.customers.index'))
            ->post(route('admin.customers.toggle-active', $customerUser->customer->id))
            ->assertRedirect(route('admin.customers.index'))
            ->assertSessionHas('success', 'تم إيقاف حساب العميل. لن يقدر يسجّل الدخول.');

        $this->assertFalse($customerUser->fresh()->is_active);

        $this->actingAs($customerUser->fresh())
            ->get(route('customer.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();

        $this->post('/login', [
            'email' => 'customer-status@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->actingAs($admin)
            ->post(route('admin.customers.toggle-active', $customerUser->customer->id))
            ->assertSessionHas('success', 'تم تفعيل حساب العميل.');

        $this->assertTrue($customerUser->fresh()->is_active);
    }

    public function test_staff_without_customer_manage_permission_cannot_change_account_status(): void
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
            ->post(route('admin.customers.toggle-active', $customerUser->customer->id))
            ->assertForbidden();

        $this->assertTrue($customerUser->fresh()->is_active);
    }
}
