<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    Store,
    ShoppingBag,
    Users,
    DollarSign,
    Receipt,
    BarChart3,
    Layers,
    Activity,
    Database,
    Settings,
    Shield,
    Moon,
    Sun,
    LogOut,
    Menu,
    X,
    ExternalLink,
    GraduationCap,
    Bike,
} from '@lucide/vue';
import type { SharedInertiaProps } from '../Types';
import { useThemeMode } from '../composables/useThemeMode';
import { usePermission } from '../composables/usePermission';

const props = defineProps<{
    title?: string;
}>();

const page = usePage<SharedInertiaProps>();
const auth = computed(() => page.props.auth);
const flash = computed(() => page.props.flash);
const { darkMode, toggleDarkMode } = useThemeMode();
const { can } = usePermission();
const sidebarOpen = ref(false);
const currentPath = computed(() => page.url.split('?')[0]);

const roleLabels: Record<string, string> = {
    SUPER_ADMIN: 'مدير عام',
    ADMIN: 'مدير',
    PLATFORM_STAFF: 'موظف منصة',
    RESTAURANT_OWNER: 'مالك مطعم',
    RESTAURANT_STAFF: 'موظف مطعم',
    DELIVERY_DRIVER: 'كابتن توصيل',
    CUSTOMER: 'عميل',
};

const roleLabel = computed(() => roleLabels[auth.value.user?.role ?? ''] ?? 'مدير المنصة');

const navGroups = [
    {
        label: 'التشغيل',
        items: [
            { label: 'غرفة التحكم', href: '/admin/dashboard', icon: LayoutDashboard, permission: 'dashboard.view' },
            { label: 'المطاعم الشريكة', href: '/admin/restaurants', icon: Store, permission: 'restaurants.view' },
            { label: 'طلبات المنصة', href: '/admin/orders', icon: ShoppingBag, permission: 'orders.view' },
            { label: 'العملاء والطلاب', href: '/admin/customers', icon: GraduationCap, permission: 'customers.view' },
            { label: 'المستخدمون', href: '/admin/users', icon: Users, permission: 'users.view' },
            { label: 'كباتن التوصيل', href: '/admin/delivery-drivers', icon: Bike, permission: 'drivers.view' },
        ],
    },
    {
        label: 'المال',
        items: [
            { label: 'الأرباح والتدفقات', href: '/admin/finance', icon: DollarSign, permission: 'finance.view' },
            { label: 'مركز التحصيل', href: '/admin/billing', icon: Receipt, permission: 'billing.view' },
            { label: 'المؤشرات', href: '/admin/analytics', icon: BarChart3, permission: 'analytics.view' },
        ],
    },
    {
        label: 'النظام',
        items: [
            { label: 'المحتوى', href: '/admin/cms', icon: Layers, permission: 'cms.manage' },
            { label: 'سجل النشاط', href: '/admin/activity-logs', icon: Activity, permission: 'activity.view' },
            { label: 'النسخ الاحتياطي', href: '/admin/backups', icon: Database, permission: 'backups.manage' },
            { label: 'الأدوار والصلاحيات', href: '/admin/roles', icon: Shield, permission: 'roles.manage' },
            { label: 'الإعدادات', href: '/admin/settings', icon: Settings, permission: 'settings.manage' },
        ],
    },
];

const visibleNavGroups = computed(() => navGroups
    .map((group) => ({
        ...group,
        items: group.items.filter((item) => can(item.permission)),
    }))
    .filter((group) => group.items.length > 0));

function isActivePath(path: string, href: string): boolean {
    if (href === '/admin/dashboard') {
        return path === href;
    }

    return path === href || path.startsWith(`${href}/`);
}

const activeLabel = computed(() => {
    const match = navGroups.flatMap((group) => group.items).find((item) => isActivePath(currentPath.value, item.href));
    return props.title || match?.label || 'غرفة التحكم';
});

const closeSidebar = (): void => {
    sidebarOpen.value = false;
};
</script>

<template>
    <div class="admin-shell min-h-screen text-stone-900 dark:text-stone-100">
        <aside class="admin-rail fixed inset-y-0 start-0 z-40 hidden w-[17.5rem] flex-col lg:flex">
            <Link href="/admin/dashboard" prefetch class="admin-brand">
                <span class="admin-mark">
                    <img src="/images/logo.png" alt="Wasla" />
                </span>
                <span>
                    <strong>منصة وصلة</strong>
                    <small>غرفة عمليات برج العرب</small>
                </span>
            </Link>

            <nav class="admin-nav custom-scrollbar">
                <div v-for="group in visibleNavGroups" :key="group.label" class="admin-nav-group">
                    <p>{{ group.label }}</p>
                    <Link
                        v-for="item in group.items"
                        :key="item.href"
                        :href="item.href"
                        prefetch
                        :class="['admin-link', isActivePath(currentPath, item.href) ? 'is-active' : '']"
                    >
                        <component :is="item.icon" class="h-4 w-4 shrink-0" />
                        <span>{{ item.label }}</span>
                    </Link>
                </div>
            </nav>

            <div class="admin-user">
                <div class="admin-avatar">{{ auth.user?.name?.charAt(0) || 'أ' }}</div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-stone-900 dark:text-stone-100">{{ auth.user?.name || 'المدير' }}</p>
                    <p class="truncate text-[11px] text-stone-400">{{ roleLabel }}</p>
                </div>
                <Link href="/logout" method="post" as="button" class="admin-icon-btn" title="تسجيل الخروج">
                    <LogOut class="h-4 w-4" />
                </Link>
            </div>
        </aside>

        <div v-if="sidebarOpen" class="fixed inset-0 z-50 flex lg:hidden">
            <button
                type="button"
                class="absolute inset-0 bg-stone-950/40"
                aria-label="إغلاق القائمة"
                @click="closeSidebar"
            />
            <div class="admin-rail relative z-10 flex h-full w-[17.5rem] flex-col shadow-2xl">
                <div class="flex items-center justify-between border-b border-stone-100 px-4 py-4 dark:border-stone-800">
                    <Link href="/admin/dashboard" class="admin-brand !border-0 !p-0" @click="closeSidebar">
                        <span class="admin-mark">
                            <img src="/images/logo.png" alt="Wasla" />
                        </span>
                        <span>
                            <strong>منصة وصلة</strong>
                            <small>القائمة</small>
                        </span>
                    </Link>
                    <button type="button" class="admin-icon-btn" aria-label="إغلاق" @click="closeSidebar">
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <nav class="admin-nav custom-scrollbar">
                    <div v-for="group in visibleNavGroups" :key="group.label" class="admin-nav-group">
                        <p>{{ group.label }}</p>
                        <Link
                            v-for="item in group.items"
                            :key="item.href"
                            :href="item.href"
                            prefetch
                            :class="['admin-link', isActivePath(currentPath, item.href) ? 'is-active' : '']"
                            @click="closeSidebar"
                        >
                            <component :is="item.icon" class="h-4 w-4 shrink-0" />
                            <span>{{ item.label }}</span>
                        </Link>
                    </div>
                </nav>
                <div class="admin-user">
                    <div class="admin-avatar">{{ auth.user?.name?.charAt(0) || 'أ' }}</div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-stone-900 dark:text-stone-100">{{ auth.user?.name || 'المدير' }}</p>
                        <p class="truncate text-[11px] text-stone-400">{{ roleLabel }}</p>
                    </div>
                    <Link href="/logout" method="post" as="button" class="admin-icon-btn" title="تسجيل الخروج">
                        <LogOut class="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </div>

        <div class="flex min-h-screen min-w-0 flex-col lg:ps-[17.5rem]">
            <header class="admin-topbar">
                <div class="flex min-w-0 items-center gap-3">
                    <button
                        type="button"
                        class="admin-icon-btn lg:hidden"
                        aria-label="فتح القائمة"
                        @click="sidebarOpen = true"
                    >
                        <Menu class="h-5 w-5" />
                    </button>
                    <div class="min-w-0">
                        <p class="admin-kicker">تشغيل المنصة</p>
                        <h1 class="truncate text-lg font-black tracking-tight text-stone-900 dark:text-white sm:text-xl">
                            {{ activeLabel }}
                        </h1>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link href="/" class="admin-chip hidden sm:inline-flex">
                        <ExternalLink class="h-3.5 w-3.5" />
                        المتجر
                    </Link>
                    <button type="button" class="admin-icon-btn" aria-label="تبديل المظهر" @click="toggleDarkMode">
                        <Sun v-if="darkMode" class="h-4 w-4 text-amber-300" />
                        <Moon v-else class="h-4 w-4" />
                    </button>
                </div>
            </header>

            <div v-if="flash?.success" class="admin-flash is-success">{{ flash.success }}</div>
            <div v-if="flash?.error" class="admin-flash is-error">{{ flash.error }}</div>

            <main class="admin-workspace flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>
