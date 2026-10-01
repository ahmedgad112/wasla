<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Home, Percent, Receipt, UserRound, ShoppingBag } from '@lucide/vue';
import { useCartStore } from '../Stores/cartStore';
import type { SharedInertiaProps } from '../Types';
import { usePermission } from '../composables/usePermission';

const page = usePage<SharedInertiaProps>();
const { can } = usePermission();
const auth = computed(() => page.props.auth);
const currentUrl = computed(() => page.url || '/');
const cart = useCartStore();
const itemCount = computed(() => cart.getItemCount);
const isLoggedIn = computed(() => Boolean(auth.value?.user));
const isCustomer = computed(() => auth.value?.user?.role === 'CUSTOMER');

const accountHref = computed(() => {
    if (!isLoggedIn.value) {
        return '/login';
    }
    if (isCustomer.value) {
        return '/customer/profile';
    }
    const role = auth.value?.user?.role;
    if (role === 'SUPER_ADMIN' || role === 'ADMIN' || role === 'PLATFORM_STAFF') {
        return '/admin/dashboard';
    }
    if (role === 'RESTAURANT_OWNER' || role === 'RESTAURANT_STAFF') {
        return '/restaurant/dashboard';
    }
    if (role === 'DELIVERY_DRIVER') {
        return '/delivery/dashboard';
    }
    return '/customer/profile';
});

const ordersHref = computed(() => (!isLoggedIn.value ? '/login' : '/customer/orders'));

const showOrders = computed(() => (isLoggedIn.value ? isCustomer.value && can('customer.orders') : true));
const showAccount = computed(() => !isCustomer.value || can('customer.profile'));
const showCart = computed(() => !isLoggedIn.value || !isCustomer.value || can('customer.orders'));

const tabs = computed(() => [
    { href: '/', label: 'الرئيسية', icon: Home, active: currentUrl.value === '/' },
    { href: '/offers', label: 'العروض', icon: Percent, active: currentUrl.value.startsWith('/offers') },
    ...(showOrders.value
        ? [{
            href: ordersHref.value,
            label: 'طلباتي',
            icon: Receipt,
            active: currentUrl.value.startsWith('/customer/orders'),
        }]
        : []),
    ...(showAccount.value
        ? [{
            href: accountHref.value,
            label: 'حسابي',
            icon: UserRound,
            active:
                currentUrl.value.startsWith('/customer/profile') ||
                currentUrl.value.startsWith('/customer/dashboard'),
        }]
        : []),
]);

const leftTabs = computed(() => tabs.value.slice(0, 2));
const rightTabs = computed(() => tabs.value.slice(2));
</script>

<template>
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-stone-200/80 bg-white/95 pb-[max(0.4rem,env(safe-area-inset-bottom))] pt-1.5 backdrop-blur-xl dark:border-stone-800 dark:bg-stone-950/95">
        <div class="mx-auto grid w-full max-w-lg grid-cols-5 items-end px-2 sm:max-w-xl">
            <Link
                v-for="tab in leftTabs"
                :key="tab.label"
                :href="tab.href"
                prefetch
                :class="[
                    'flex flex-col items-center gap-0.5 py-1 text-[10px] font-bold',
                    tab.active ? 'text-orange-600 dark:text-orange-400' : 'text-stone-400 dark:text-stone-500',
                ]"
            >
                <component :is="tab.icon" :class="['h-5 w-5', tab.active ? 'stroke-[2.4]' : '']" />
                <span>{{ tab.label }}</span>
            </Link>

            <button v-if="showCart" type="button" class="relative -mt-6 flex flex-col items-center" aria-label="السلة" @click="cart.openCart()">
                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-orange-500 to-amber-500 text-white shadow-lg shadow-orange-500/40">
                    <ShoppingBag class="h-6 w-6" />
                </span>
                <span
                    v-if="itemCount > 0"
                    class="absolute -top-1 left-1/2 flex h-5 min-w-5 -translate-x-1/2 items-center justify-center rounded-full bg-stone-900 px-1 text-[10px] font-black text-white dark:bg-white dark:text-stone-900"
                >
                    {{ itemCount }}
                </span>
                <span class="mt-1 text-[10px] font-bold text-orange-600 dark:text-orange-400">السلة</span>
            </button>

            <Link
                v-for="tab in rightTabs"
                :key="tab.label"
                :href="tab.href"
                prefetch
                :class="[
                    'flex flex-col items-center gap-0.5 py-1 text-[10px] font-bold',
                    tab.active ? 'text-orange-600 dark:text-orange-400' : 'text-stone-400 dark:text-stone-500',
                ]"
            >
                <component :is="tab.icon" :class="['h-5 w-5', tab.active ? 'stroke-[2.4]' : '']" />
                <span>{{ tab.label }}</span>
            </Link>
        </div>
    </nav>
</template>
