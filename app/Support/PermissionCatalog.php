<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionCatalog
{
    public const UNMAPPED = 'system.unmapped';

    /**
     * @return list<string>
     */
    public static function roleNames(): array
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

    public static function roleLabel(string $role): string
    {
        return [
            'SUPER_ADMIN' => 'مدير عام',
            'ADMIN' => 'مدير',
            'PLATFORM_STAFF' => 'موظف منصة',
            'RESTAURANT_OWNER' => 'مالك مطعم',
            'RESTAURANT_STAFF' => 'موظف مطعم',
            'DELIVERY_DRIVER' => 'كابتن توصيل',
            'CUSTOMER' => 'عميل',
        ][$role] ?? $role;
    }

    /**
     * @return list<array{key: string, label: string, permissions: list<array{name: string, label: string}>}>
     */
    public static function grouped(): array
    {
        return [
            [
                'key' => 'platform',
                'label' => 'تشغيل المنصة',
                'permissions' => [
                    ['name' => 'dashboard.view', 'label' => 'غرفة التحكم'],
                    ['name' => 'restaurants.view', 'label' => 'عرض المطاعم'],
                    ['name' => 'restaurants.create', 'label' => 'إضافة مطعم'],
                    ['name' => 'restaurants.update', 'label' => 'تعديل المطعم وإيقافه'],
                    ['name' => 'restaurants.delete', 'label' => 'حذف مطعم'],
                    ['name' => 'orders.view', 'label' => 'طلبات المنصة'],
                    ['name' => 'customers.view', 'label' => 'عرض العملاء'],
                    ['name' => 'customers.manage', 'label' => 'توثيق ورفض الطلاب'],
                    ['name' => 'users.view', 'label' => 'عرض المستخدمين'],
                    ['name' => 'users.manage', 'label' => 'إضافة وتعديل وحذف المستخدمين'],
                    ['name' => 'drivers.view', 'label' => 'عرض كباتن التوصيل'],
                    ['name' => 'drivers.manage', 'label' => 'إضافة وتفعيل وحذف الكباتن'],
                ],
            ],
            [
                'key' => 'money',
                'label' => 'المال والتقارير',
                'permissions' => [
                    ['name' => 'finance.view', 'label' => 'عرض الأرباح والمصروفات'],
                    ['name' => 'finance.manage', 'label' => 'إضافة وحذف المصروفات'],
                    ['name' => 'billing.view', 'label' => 'عرض مركز التحصيل'],
                    ['name' => 'billing.manage', 'label' => 'إصدار الفواتير والتحصيل والقفل'],
                    ['name' => 'analytics.view', 'label' => 'المؤشرات'],
                ],
            ],
            [
                'key' => 'system',
                'label' => 'النظام',
                'permissions' => [
                    ['name' => 'cms.manage', 'label' => 'محتوى الصفحة الرئيسية'],
                    ['name' => 'activity.view', 'label' => 'سجل النشاط'],
                    ['name' => 'backups.manage', 'label' => 'النسخ الاحتياطي'],
                    ['name' => 'settings.manage', 'label' => 'إعدادات المنصة'],
                    ['name' => 'roles.manage', 'label' => 'الأدوار والصلاحيات'],
                ],
            ],
            [
                'key' => 'restaurant',
                'label' => 'لوحة المطعم',
                'permissions' => [
                    ['name' => 'restaurant.dashboard', 'label' => 'رئيسية المطعم'],
                    ['name' => 'restaurant.orders', 'label' => 'طلبات المطعم'],
                    ['name' => 'restaurant.menu', 'label' => 'المنيو والتصنيفات'],
                    ['name' => 'restaurant.offers', 'label' => 'عروض المطعم'],
                    ['name' => 'restaurant.drivers', 'label' => 'كباتن المطعم ونشاطهم'],
                    ['name' => 'restaurant.billing', 'label' => 'فواتير المطعم'],
                    ['name' => 'restaurant.analytics', 'label' => 'تقارير المطعم'],
                    ['name' => 'restaurant.settings', 'label' => 'إعدادات المطعم وحالة الفتح'],
                ],
            ],
            [
                'key' => 'delivery',
                'label' => 'تطبيق الكابتن',
                'permissions' => [
                    ['name' => 'delivery.orders', 'label' => 'الطلبات والتوصيل'],
                    ['name' => 'delivery.profile', 'label' => 'حساب الكابتن'],
                ],
            ],
            [
                'key' => 'customer',
                'label' => 'حساب العميل',
                'permissions' => [
                    ['name' => 'customer.orders', 'label' => 'السلة والطلب وسجل الطلبات'],
                    ['name' => 'customer.profile', 'label' => 'الملف والعناوين وتوثيق الطالب'],
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        $names = [];

        foreach (self::grouped() as $group) {
            foreach ($group['permissions'] as $permission) {
                $names[] = $permission['name'];
            }
        }

        return $names;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function defaultGrants(): array
    {
        $all = self::names();
        $restaurant = array_values(array_filter(
            $all,
            fn (string $name): bool => str_starts_with($name, 'restaurant.')
        ));

        return [
            'SUPER_ADMIN' => $all,
            'ADMIN' => array_values(array_filter(
                $all,
                fn (string $name): bool => $name !== 'roles.manage'
            )),
            'PLATFORM_STAFF' => [
                'dashboard.view',
                'restaurants.view',
                'orders.view',
                'customers.view',
                'customers.manage',
                'users.view',
                'users.manage',
                'drivers.view',
            ],
            'RESTAURANT_OWNER' => $restaurant,
            'RESTAURANT_STAFF' => [
                'restaurant.dashboard',
                'restaurant.orders',
                'restaurant.menu',
                'restaurant.offers',
            ],
            'DELIVERY_DRIVER' => [
                'delivery.orders',
                'delivery.profile',
            ],
            'CUSTOMER' => [
                'customer.orders',
                'customer.profile',
            ],
        ];
    }

    public static function forRoute(?string $name, string $method): ?string
    {
        if (! is_string($name) || $name === '') {
            return null;
        }

        if ($name === 'delivery.suspended') {
            return null;
        }

        $method = strtoupper($method);
        $rules = self::routeRules();

        usort($rules, function (array $left, array $right): int {
            $length = strlen($right['prefix']) <=> strlen($left['prefix']);

            if ($length !== 0) {
                return $length;
            }

            $leftMethods = isset($left['methods']) ? 0 : 1;
            $rightMethods = isset($right['methods']) ? 0 : 1;

            return $leftMethods <=> $rightMethods;
        });

        foreach ($rules as $rule) {
            if (! self::nameMatches($name, $rule['prefix'])) {
                continue;
            }

            if (isset($rule['methods']) && ! in_array($method, $rule['methods'], true)) {
                continue;
            }

            return $rule['permission'];
        }

        if (
            str_starts_with($name, 'admin.')
            || str_starts_with($name, 'restaurant.')
            || str_starts_with($name, 'delivery.')
            || str_starts_with($name, 'customer.')
        ) {
            return self::UNMAPPED;
        }

        return null;
    }

    public static function syncDefaults(bool $overwriteExistingGrants = true): void
    {
        foreach (self::roleNames() as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        foreach (self::names() as $permissionName) {
            Permission::findOrCreate($permissionName, 'web');
        }

        foreach (self::defaultGrants() as $roleName => $permissions) {
            $role = Role::findByName($roleName, 'web');

            if ($roleName === 'SUPER_ADMIN' || $overwriteExistingGrants || $role->permissions()->doesntExist()) {
                $role->syncPermissions($permissions);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        User::query()
            ->whereIn('role', self::roleNames())
            ->orderBy('id')
            ->each(function (User $user): void {
                if (! $user->hasRole($user->role)) {
                    $user->assignRole($user->role);
                }

                cache()->forget("user.{$user->id}.permissions");
            });
    }

    /**
     * @return list<array{prefix: string, permission: string, methods?: list<string>}>
     */
    private static function routeRules(): array
    {
        $write = ['POST', 'PUT', 'PATCH', 'DELETE'];

        return [
            ['prefix' => 'admin.roles', 'permission' => 'roles.manage'],
            ['prefix' => 'admin.dashboard', 'permission' => 'dashboard.view'],
            ['prefix' => 'admin.restaurants.delete', 'permission' => 'restaurants.delete'],
            ['prefix' => 'admin.restaurants.destroy', 'permission' => 'restaurants.delete'],
            ['prefix' => 'admin.restaurants.create', 'permission' => 'restaurants.create'],
            ['prefix' => 'admin.restaurants.store', 'permission' => 'restaurants.create'],
            ['prefix' => 'admin.restaurants.edit', 'permission' => 'restaurants.update'],
            ['prefix' => 'admin.restaurants.update', 'permission' => 'restaurants.update'],
            ['prefix' => 'admin.restaurants.suspend', 'permission' => 'restaurants.update'],
            ['prefix' => 'admin.restaurants.activate', 'permission' => 'restaurants.update'],
            ['prefix' => 'admin.restaurants.financial-config', 'permission' => 'restaurants.update'],
            ['prefix' => 'admin.restaurants', 'permission' => 'restaurants.view'],
            ['prefix' => 'admin.orders', 'permission' => 'orders.view'],
            ['prefix' => 'admin.customers.verify-student', 'permission' => 'customers.manage'],
            ['prefix' => 'admin.customers.reject-student', 'permission' => 'customers.manage'],
            ['prefix' => 'admin.customers', 'permission' => 'customers.view'],
            ['prefix' => 'admin.users.toggle-active', 'permission' => 'users.manage'],
            ['prefix' => 'admin.users.create', 'permission' => 'users.manage'],
            ['prefix' => 'admin.users.store', 'permission' => 'users.manage'],
            ['prefix' => 'admin.users.edit', 'permission' => 'users.manage'],
            ['prefix' => 'admin.users.update', 'permission' => 'users.manage'],
            ['prefix' => 'admin.users.destroy', 'permission' => 'users.manage'],
            ['prefix' => 'admin.users', 'permission' => 'users.view'],
            ['prefix' => 'admin.delivery-drivers.create', 'permission' => 'drivers.manage'],
            ['prefix' => 'admin.delivery-drivers.store', 'permission' => 'drivers.manage'],
            ['prefix' => 'admin.delivery-drivers.destroy', 'permission' => 'drivers.manage'],
            ['prefix' => 'admin.delivery-drivers.toggle', 'permission' => 'drivers.manage'],
            ['prefix' => 'admin.delivery-drivers', 'permission' => 'drivers.view'],
            ['prefix' => 'admin.finance', 'methods' => $write, 'permission' => 'finance.manage'],
            ['prefix' => 'admin.finance', 'permission' => 'finance.view'],
            ['prefix' => 'admin.billing', 'methods' => $write, 'permission' => 'billing.manage'],
            ['prefix' => 'admin.billing', 'permission' => 'billing.view'],
            ['prefix' => 'admin.invoices', 'methods' => $write, 'permission' => 'billing.manage'],
            ['prefix' => 'admin.invoices', 'permission' => 'billing.view'],
            ['prefix' => 'admin.collections', 'methods' => $write, 'permission' => 'billing.manage'],
            ['prefix' => 'admin.collections', 'permission' => 'billing.view'],
            ['prefix' => 'admin.analytics', 'permission' => 'analytics.view'],
            ['prefix' => 'admin.cms', 'permission' => 'cms.manage'],
            ['prefix' => 'admin.activity-logs', 'permission' => 'activity.view'],
            ['prefix' => 'admin.backups', 'permission' => 'backups.manage'],
            ['prefix' => 'admin.settings', 'permission' => 'settings.manage'],
            ['prefix' => 'restaurant.dashboard', 'permission' => 'restaurant.dashboard'],
            ['prefix' => 'restaurant.orders', 'permission' => 'restaurant.orders'],
            ['prefix' => 'restaurant.categories', 'permission' => 'restaurant.menu'],
            ['prefix' => 'restaurant.menu', 'permission' => 'restaurant.menu'],
            ['prefix' => 'restaurant.offers', 'permission' => 'restaurant.offers'],
            ['prefix' => 'restaurant.delivery-drivers', 'permission' => 'restaurant.drivers'],
            ['prefix' => 'restaurant.drivers', 'permission' => 'restaurant.drivers'],
            ['prefix' => 'restaurant.driver-stats', 'permission' => 'restaurant.drivers'],
            ['prefix' => 'restaurant.billing', 'permission' => 'restaurant.billing'],
            ['prefix' => 'restaurant.analytics', 'permission' => 'restaurant.analytics'],
            ['prefix' => 'restaurant.settings', 'permission' => 'restaurant.settings'],
            ['prefix' => 'delivery.profile', 'permission' => 'delivery.profile'],
            ['prefix' => 'delivery.', 'permission' => 'delivery.orders'],
            ['prefix' => 'customer.profile', 'permission' => 'customer.profile'],
            ['prefix' => 'customer.address', 'permission' => 'customer.profile'],
            ['prefix' => 'customer.student', 'permission' => 'customer.profile'],
            ['prefix' => 'customer.', 'permission' => 'customer.orders'],
        ];
    }

    private static function nameMatches(string $name, string $prefix): bool
    {
        if (str_ends_with($prefix, '.')) {
            return str_starts_with($name, $prefix);
        }

        return $name === $prefix || str_starts_with($name, $prefix.'.');
    }
}
