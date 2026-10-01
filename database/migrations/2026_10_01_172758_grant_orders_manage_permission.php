<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::findOrCreate('orders.manage', 'web');

        foreach (['SUPER_ADMIN', 'ADMIN'] as $roleName) {
            $role = Role::findOrCreate($roleName, 'web');

            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        User::query()
            ->whereIn('role', ['SUPER_ADMIN', 'ADMIN'])
            ->pluck('id')
            ->each(function (int $id): void {
                cache()->forget("user.{$id}.permissions");
            });
    }

    public function down(): void
    {
        $permission = Permission::query()
            ->where('name', 'orders.manage')
            ->where('guard_name', 'web')
            ->first();

        if ($permission === null) {
            return;
        }

        foreach (['SUPER_ADMIN', 'ADMIN'] as $roleName) {
            $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role !== null && $role->hasPermissionTo($permission)) {
                $role->revokePermissionTo($permission);
            }
        }

        $permission->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
