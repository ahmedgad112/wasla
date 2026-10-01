<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::with('roles')
            ->whereIn('role', ['SUPER_ADMIN', 'ADMIN', 'PLATFORM_STAFF'])
            ->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $roles = Role::pluck('name');
        if ($roles->isEmpty()) {
            $roles = collect(['SUPER_ADMIN', 'ADMIN', 'PLATFORM_STAFF', 'RESTAURANT_OWNER', 'RESTAURANT_STAFF', 'DELIVERY_DRIVER', 'CUSTOMER']);
        }

        return Inertia::render('Admin/Users/Index', [
            'users' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'role']),
            'roles' => $roles,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'roles' => $this->formRoles(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'role' => ['required', Rule::in($this->assignableRoles())],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً لدى مستخدم آخر.',
            'phone.unique' => 'رقم الهاتف مسجل مسبقاً لدى مستخدم آخر.',
            'role.in' => 'الدور المحدد غير مسموح.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق.',
        ]);

        $this->assertCanAssignRole($request->user(), $validated['role']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        Role::findOrCreate($validated['role'], 'web');
        $user->assignRole($validated['role']);
        ActivityLog::log('USER_CREATED', 'User', $user->id, null, ['role' => $validated['role']]);

        return redirect()->route('admin.users.index')
            ->with('success', 'تم إنشاء المستخدم بنجاح.');
    }

    public function edit(int $id): Response
    {
        $user = User::findOrFail($id);

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'roles' => $this->formRoles(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'password' => ['nullable', Password::min(8)],
            'role' => ['required', Rule::in($this->assignableRoles())],
            'is_active' => ['required', 'boolean'],
        ], [
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً لدى مستخدم آخر.',
            'phone.unique' => 'رقم الهاتف مسجل مسبقاً لدى مستخدم آخر.',
            'role.in' => 'الدور المحدد غير مسموح.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 8 أحرف.',
        ]);

        $this->assertCanAssignRole($request->user(), $validated['role'], $user);

        $removesLastSuperAdmin = $user->isSuperAdmin()
            && ($validated['role'] !== 'SUPER_ADMIN' || ! $validated['is_active'])
            && ! User::query()
                ->where('role', 'SUPER_ADMIN')
                ->where('is_active', true)
                ->whereKeyNot($user->id)
                ->exists();

        if ($removesLastSuperAdmin) {
            throw ValidationException::withMessages([
                'role' => 'لا يمكن تغيير دور آخر مدير عام أو تعطيل حسابه.',
            ]);
        }

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'is_active' => $validated['is_active'],
        ];

        if ($request->filled('password')) {
            $attributes['password'] = $validated['password'];
        }

        $user->update($attributes);
        Role::findOrCreate($validated['role'], 'web');
        $user->syncRoles([$validated['role']]);
        ActivityLog::log('USER_UPDATED', 'User', $user->id);

        return redirect()->route('admin.users.index')
            ->with('success', 'تم تحديث بيانات المستخدم.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        if ($user->isSuperAdmin()) {
            return back()->with('error', 'لا يمكن حذف حساب Super Admin.');
        }
        ActivityLog::log('USER_DELETED', 'User', $user->id);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'تم حذف المستخدم.');
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $user = User::findOrFail($id);
        $actor = auth()->user();

        if ($user->isSuperAdmin() && ! $actor?->isSuperAdmin()) {
            return back()->with('error', 'فقط المدير العام يمكنه تعطيل حساب مدير عام.');
        }

        if (
            $user->isSuperAdmin()
            && $user->is_active
            && ! User::query()
                ->where('role', 'SUPER_ADMIN')
                ->where('is_active', true)
                ->whereKeyNot($user->id)
                ->exists()
        ) {
            return back()->with('error', 'لا يمكن تعطيل آخر مدير عام.');
        }

        $user->update(['is_active' => ! $user->is_active]);
        $status = $user->is_active ? 'تفعيل' : 'تعطيل';
        ActivityLog::log('USER_TOGGLE_ACTIVE', 'User', $user->id, null, ['is_active' => $user->is_active]);

        return back()->with('success', "تم {$status} المستخدم.");
    }

    /**
     * @return Collection<int, string>
     */
    private function formRoles(): Collection
    {
        $roles = Role::pluck('name');

        if ($roles->isEmpty()) {
            $roles = collect($this->assignableRoles());
        }

        if (! auth()->user()?->isSuperAdmin()) {
            $roles = $roles->reject(fn (string $role): bool => $role === 'SUPER_ADMIN')->values();
        }

        return $roles;
    }

    private function assertCanAssignRole(?User $actor, string $role, ?User $target = null): void
    {
        if ($actor?->isSuperAdmin()) {
            return;
        }

        if ($role === 'SUPER_ADMIN' || $target?->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'role' => 'فقط المدير العام يمكنه تعديل حساب مدير عام أو منح هذا الدور.',
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function assignableRoles(): array
    {
        return [
            'SUPER_ADMIN',
            'ADMIN',
            'PLATFORM_STAFF',
            'RESTAURANT_OWNER',
            'RESTAURANT_STAFF',
            'DELIVERY_DRIVER',
            'CUSTOMER',
        ];
    }
}
