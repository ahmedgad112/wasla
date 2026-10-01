<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    ShoppingBag,
    UtensilsCrossed,
    Layers,
    Tag,
    Bike,
    BarChart3,
    Settings,
    Moon,
    Sun,
    LogOut,
    Menu,
    X,
    Activity,
    Receipt,
} from '@lucide/vue';
import type { AvailabilityStatus, SharedInertiaProps } from '../Types';
import { useThemeMode } from '../composables/useThemeMode';
import { availabilityMeta, resolveAvailability } from '../lib/restaurantAvailability';
import { usePermission } from '../composables/usePermission';

const props = defineProps<{
    title?: string;
    restaurantName?: string;
    isOpen?: boolean;
}>();

const page = usePage<SharedInertiaProps>();
const auth = computed(() => page.props.auth);
const flash = computed(() => page.props.flash);
const shellRestaurant = computed(() => page.props.shell_restaurant);
const { darkMode, toggleDarkMode } = useThemeMode();
const { can } = usePermission();
const sidebarOpen = ref(false);
const updating = ref(false);
const currentPath = computed(() => page.url.split('?')[0]);

const navItems = [
    { label: 'الرئيسية', href: '/restaurant/dashboard', icon: LayoutDashboard, permission: 'restaurant.dashboard' },
    { label: 'الطلبات', href: '/restaurant/orders', icon: ShoppingBag, permission: 'restaurant.orders' },
    { label: 'المنيو', href: '/restaurant/menu', icon: UtensilsCrossed, permission: 'restaurant.menu' },
    { label: 'التصنيفات', href: '/restaurant/categories', icon: Layers, permission: 'restaurant.menu' },
    { label: 'العروض', href: '/restaurant/offers', icon: Tag, permission: 'restaurant.offers' },
    { label: 'الكباتن', href: '/restaurant/delivery-drivers', icon: Bike, permission: 'restaurant.drivers' },
    { label: 'نشاط التوصيل', href: '/restaurant/driver-stats', icon: Activity, permission: 'restaurant.drivers' },
    { label: 'الفواتير', href: '/restaurant/billing', icon: Receipt, permission: 'restaurant.billing' },
    { label: 'التقارير', href: '/restaurant/analytics', icon: BarChart3, permission: 'restaurant.analytics' },
    { label: 'الإعدادات', href: '/restaurant/settings', icon: Settings, permission: 'restaurant.settings' },
];

const visibleNavItems = computed(() => navItems.filter((item) => can(item.permission)));

const availabilityOptions: { value: AvailabilityStatus; label: string; activeClass: string }[] = [
    { value: 'OPEN', label: 'مفتوح', activeClass: 'bg-emerald-500 text-white shadow-sm' },
    { value: 'BUSY', label: 'مشغول', activeClass: 'bg-amber-500 text-white shadow-sm' },
    { value: 'CLOSED', label: 'مغلق', activeClass: 'bg-stone-700 text-white shadow-sm' },
];

function isActivePath(path: string, href: string): boolean {
    if (href === '/restaurant/dashboard') {
        return path === href;
    }

    return path === href || path.startsWith(`${href}/`);
}

const branchName = computed(
    () => props.restaurantName || shellRestaurant.value?.name || auth.value.user?.name || 'المطعم',
);
const accountActive = computed(() => shellRestaurant.value?.status === 'ACTIVE');
const availability = computed(() => resolveAvailability(shellRestaurant.value));
const meta = computed(() => availabilityMeta(availability.value));

const activeLabel = computed(() => {
    const match = navItems.find((item) => isActivePath(currentPath.value, item.href));
    return props.title || match?.label || 'الرئيسية';
});

const availabilityStatusClass = computed(() => {
    if (availability.value === 'OPEN') {
        return 'text-emerald-600';
    }
    if (availability.value === 'BUSY') {
        return 'text-amber-600';
    }
    return 'text-stone-400';
});

const setAvailability = (status: AvailabilityStatus): void => {
    if (updating.value || !accountActive.value || status === availability.value) {
        return;
    }

    updating.value = true;
    router.post(
        '/restaurant/settings/availability',
        { availability_status: status },
        {
            preserveScroll: true,
            onFinish: () => {
                updating.value = false;
            },
        },
    );
};

const closeSidebar = (): void => {
    sidebarOpen.value = false;
};
</script>

<template>
    <div class="restaurant-shell min-h-screen text-stone-900 dark:text-stone-100">
        <aside class="admin-rail fixed inset-y-0 start-0 z-40 hidden w-[17.5rem] flex-col lg:flex">
            <Link href="/restaurant/dashboard" prefetch class="admin-brand">
                <span class="admin-mark">
                    <img src="/images/logo.png" alt="Wasla" />
                </span>
                <span>
                    <strong class="truncate">{{ branchName }}</strong>
                    <small>لوحة تحكم المطعم</small>
                </span>
            </Link>

            <nav class="admin-nav custom-scrollbar">
                <div class="admin-nav-group">
                    <p>القائمة</p>
                    <Link
                        v-for="item in visibleNavItems"
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
                <div class="admin-avatar">{{ auth.user?.name?.charAt(0) || 'م' }}</div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-stone-900 dark:text-stone-100">
                        {{ auth.user?.name || 'المالك' }}
                    </p>
                    <p :class="['truncate text-[11px] font-bold', availabilityStatusClass]">
                        {{ meta.label }}
                    </p>
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
                    <span class="text-sm font-black text-stone-900 dark:text-white">{{ branchName }}</span>
                    <button type="button" class="admin-icon-btn" aria-label="إغلاق" @click="closeSidebar">
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <nav class="admin-nav">
                    <Link
                        v-for="item in visibleNavItems"
                        :key="item.href"
                        :href="item.href"
                        prefetch
                        :class="['admin-link', isActivePath(currentPath, item.href) ? 'is-active' : '']"
                        @click="closeSidebar"
                    >
                        <component :is="item.icon" class="h-4 w-4 shrink-0" />
                        <span>{{ item.label }}</span>
                    </Link>
                </nav>
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
                        <p class="admin-kicker">وصلة</p>
                        <h1 class="truncate text-lg font-black tracking-tight text-stone-900 dark:text-white sm:text-xl">
                            {{ activeLabel }}
                        </h1>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div
                        v-if="can('restaurant.settings')"
                        :class="[
                            'inline-flex rounded-full bg-stone-100 p-1 ring-1 ring-stone-200 dark:bg-stone-800 dark:ring-stone-700',
                            !accountActive || updating ? 'opacity-60' : '',
                        ]"
                        :title="!accountActive ? 'الحساب غير مفعّل من الإدارة' : 'حالة المطعم للعملاء'"
                    >
                        <button
                            v-for="option in availabilityOptions"
                            :key="option.value"
                            type="button"
                            :disabled="updating || !accountActive"
                            :class="[
                                'rounded-full px-2.5 py-1.5 text-[11px] font-black transition sm:px-3',
                                availability === option.value
                                    ? option.activeClass
                                    : 'text-stone-500 hover:text-stone-800 dark:text-stone-400',
                            ]"
                            @click="setAvailability(option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                    <button type="button" class="admin-icon-btn" aria-label="تبديل المظهر" @click="toggleDarkMode">
                        <Sun v-if="darkMode" class="h-4 w-4 text-amber-300" />
                        <Moon v-else class="h-4 w-4" />
                    </button>
                </div>
            </header>

            <div v-if="flash?.success" class="admin-flash is-success">{{ flash.success }}</div>
            <div v-if="flash?.error" class="admin-flash is-error">{{ flash.error }}</div>

            <main class="admin-workspace restaurant-workspace flex-1">
                <slot />
            </main>
        </div>
    </div>
</template>
