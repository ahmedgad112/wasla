<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(string $role = 'SUPER_ADMIN'): User
    {
        Role::findOrCreate($role, 'web');

        $admin = User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
        $admin->assignRole($role);

        return $admin;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function platformUser(array $overrides = []): User
    {
        Role::findOrCreate('SUPER_ADMIN', 'web');

        $user = User::factory()->create(array_merge([
            'name' => 'المدير العام',
            'email' => 'manager@example.com',
            'phone' => '01019747118',
            'role' => 'SUPER_ADMIN',
            'is_active' => true,
        ], $overrides));
        $user->assignRole($user->role);

        return $user;
    }

    public function test_super_admin_can_save_general_manager_without_changing_role(): void
    {
        $admin = $this->adminUser();
        $manager = $this->platformUser();

        $this->actingAs($admin)
            ->put("/admin/users/{$manager->id}", [
                'name' => 'المدير العام المحدّث',
                'email' => 'manager@example.com',
                'phone' => '01019747119',
                'password' => '',
                'role' => 'SUPER_ADMIN',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $manager->refresh();
        $this->assertSame('المدير العام المحدّث', $manager->name);
        $this->assertSame('01019747119', $manager->phone);
        $this->assertSame('SUPER_ADMIN', $manager->role);
        $this->assertTrue($manager->is_active);
        $this->assertTrue($manager->hasRole('SUPER_ADMIN'));
    }

    public function test_user_update_changes_password_only_when_a_new_one_is_provided(): void
    {
        $admin = $this->adminUser();
        $manager = $this->platformUser([
            'password' => 'old-password',
        ]);

        $this->actingAs($admin)
            ->put("/admin/users/{$manager->id}", [
                'name' => 'المدير العام',
                'email' => 'manager@example.com',
                'phone' => '01019747118',
                'password' => 'new-password',
                'role' => 'SUPER_ADMIN',
                'is_active' => false,
            ])
            ->assertRedirect(route('admin.users.index'));

        $manager->refresh();
        $this->assertTrue(Hash::check('new-password', $manager->password));
        $this->assertFalse($manager->is_active);

        $this->actingAs($admin)
            ->put("/admin/users/{$manager->id}", [
                'name' => 'المدير العام',
                'email' => 'manager@example.com',
                'phone' => '01019747118',
                'password' => '',
                'role' => 'SUPER_ADMIN',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue(Hash::check('new-password', $manager->fresh()->password));
    }

    public function test_user_update_rejects_an_unknown_role(): void
    {
        $admin = $this->adminUser();
        $manager = $this->platformUser();

        $this->actingAs($admin)
            ->from("/admin/users/{$manager->id}/edit")
            ->put("/admin/users/{$manager->id}", [
                'name' => 'اسم جديد',
                'email' => 'manager@example.com',
                'phone' => '01019747118',
                'password' => '',
                'role' => 'OWNER',
                'is_active' => true,
            ])
            ->assertRedirect("/admin/users/{$manager->id}/edit")
            ->assertSessionHasErrors('role');

        $this->assertSame('المدير العام', $manager->fresh()->name);
    }

    public function test_staff_cannot_grant_or_edit_super_admin(): void
    {
        $staff = $this->adminUser('PLATFORM_STAFF');
        $manager = $this->platformUser();

        $this->actingAs($staff)
            ->from("/admin/users/{$manager->id}/edit")
            ->put("/admin/users/{$manager->id}", [
                'name' => 'محاولة تعديل',
                'email' => 'manager@example.com',
                'phone' => '01019747118',
                'password' => '',
                'role' => 'SUPER_ADMIN',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame('المدير العام', $manager->fresh()->name);
    }

    public function test_last_super_admin_cannot_be_deactivated(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->from("/admin/users/{$admin->id}/edit")
            ->put("/admin/users/{$admin->id}", [
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => '01000000000',
                'password' => '',
                'role' => 'ADMIN',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('role');

        $this->assertSame('SUPER_ADMIN', $admin->fresh()->role);
    }
}
