<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(): Response
    {
        $roles = collect(PermissionCatalog::roleNames())->map(function (string $name): array {
            $role = Role::findByName($name, 'web');

            return [
                'name' => $name,
                'label' => PermissionCatalog::roleLabel($name),
                'users_count' => $role->users()->count(),
                'permissions' => $role->permissions->pluck('name')->values()->all(),
            ];
        })->values()->all();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'catalog' => PermissionCatalog::grouped(),
        ]);
    }

    public function update(Request $request, string $role): RedirectResponse
    {
        if (! in_array($role, PermissionCatalog::roleNames(), true)) {
            abort(404);
        }

        $validated = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', Rule::in(PermissionCatalog::names())],
        ]);

        $roleModel = Role::findByName($role, 'web');
        $previous = $roleModel->permissions->pluck('name')->values()->all();
        $permissions = $role === 'SUPER_ADMIN'
            ? PermissionCatalog::names()
            : $validated['permissions'];
        $roleModel->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $userIds = User::query()->where('role', $role)->pluck('id')
            ->merge(User::role($role)->pluck('id'))
            ->unique();

        foreach ($userIds as $userId) {
            cache()->forget("user.{$userId}.permissions");
        }

        ActivityLog::log('ROLE_PERMISSIONS_UPDATED', 'Role', $roleModel->id, [
            'role' => $role,
            'permissions' => $previous,
        ], [
            'role' => $role,
            'permissions' => $permissions,
        ]);

        return back()->with('success', 'تم تحديث صلاحيات '.PermissionCatalog::roleLabel($role).'.');
    }
}
