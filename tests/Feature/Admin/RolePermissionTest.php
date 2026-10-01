<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        Role::findOrCreate($role, 'web');

        $user = User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_super_admin_can_update_another_roles_permissions(): void
    {
        $admin = $this->userWithRole('SUPER_ADMIN');

        $this->actingAs($admin)
            ->put(route('admin.roles.update', 'PLATFORM_STAFF'), [
                'permissions' => ['dashboard.view', 'orders.view'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $role = Role::findByName('PLATFORM_STAFF', 'web');
        $this->assertEqualsCanonicalizing(
            ['dashboard.view', 'orders.view'],
            $role->permissions->pluck('name')->all(),
        );
    }

    public function test_super_admin_keeps_every_permission_and_every_admin_page(): void
    {
        $admin = $this->userWithRole('SUPER_ADMIN');
        Role::findByName('SUPER_ADMIN', 'web')->syncPermissions(['dashboard.view']);

        $this->actingAs($admin)
            ->put(route('admin.roles.update', 'SUPER_ADMIN'), [
                'permissions' => ['dashboard.view', 'roles.manage'],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEqualsCanonicalizing(
            PermissionCatalog::names(),
            Role::findByName('SUPER_ADMIN', 'web')->permissions->pluck('name')->all(),
        );

        $this->actingAs($admin)
            ->get(route('admin.customers.index'))
            ->assertOk();
        $this->actingAs($admin)
            ->get(route('admin.billing.index'))
            ->assertOk();
        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertOk();
    }

    public function test_admin_cannot_open_the_roles_page(): void
    {
        $admin = $this->userWithRole('ADMIN');

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    }

    public function test_staff_without_delete_permission_cannot_delete_a_restaurant(): void
    {
        $staff = $this->userWithRole('PLATFORM_STAFF');
        Role::findByName('PLATFORM_STAFF', 'web')->syncPermissions(['restaurants.view']);

        $this->actingAs($staff)
            ->post('/admin/restaurants/1/delete')
            ->assertForbidden();
    }

    public function test_staff_can_still_open_the_restaurant_list(): void
    {
        $staff = $this->userWithRole('PLATFORM_STAFF');

        $this->actingAs($staff)
            ->get(route('admin.restaurants.index'))
            ->assertOk();
    }

    public function test_catalog_covers_every_portal_permission_used_by_routes(): void
    {
        $this->assertContains('roles.manage', PermissionCatalog::names());
        $this->assertContains('restaurant.settings', PermissionCatalog::defaultGrants()['RESTAURANT_OWNER']);
        $this->assertNotContains('roles.manage', PermissionCatalog::defaultGrants()['ADMIN']);
        $this->assertSame('restaurants.delete', PermissionCatalog::forRoute('admin.restaurants.delete', 'POST'));
        $this->assertSame('users.manage', PermissionCatalog::forRoute('admin.users.update', 'PUT'));
        $this->assertSame('customer.profile', PermissionCatalog::forRoute('customer.address.store', 'POST'));
        $this->assertNull(PermissionCatalog::forRoute('delivery.suspended', 'GET'));
    }
}
