<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Moon,
    Sun,
    MapPin,
    ChevronLeft,
    User as UserIcon,
    LogOut,
} from '@lucide/vue';
import type { SharedInertiaProps } from '../Types';
import CartDrawer from '../Components/CartDrawer.vue';
import AppBottomNav from '../Components/AppBottomNav.vue';
import { useAvailabilityStore } from '../Stores/availabilityStore';

const props = withDefaults(
    defineProps<{
        title?: string;
        hideBottomNav?: boolean;
        hideHeader?: boolean;
    }>(),
    {
        hideBottomNav: false,
        hideHeader: false,
    },
);

const AUTH_PATHS = [
    '/login',
    '/register',
    '/admin/login',
    '/restaurant/login',
    '/delivery/login',
];

function applyTheme(isDark: boolean): void {
    document.documentElement.classList.toggle('dark', isDark);
    localStorage.setItem('fatrna_theme', isDark ? 'dark' : 'light');
}

const page = usePage<SharedInertiaProps>();
const auth = computed(() => page.props.auth);
const flash = computed(() => page.props.flash);
const supportPhone = computed(() => page.props.support_phone);
const url = computed(() => page.url);
const darkMode = ref(false);
const availabilityStore = useAvailabilityStore();

const isAuthScreen = computed(() =>
    AUTH_PATHS.some((path) => url.value === path || url.value.startsWith(`${path}?`)),
);
const showBottomNav = computed(() => (props.hideBottomNav === true ? false : !isAuthScreen.value));

onMounted(() => {
    applyTheme(false);
    darkMode.value = false;
});

watch(
    isAuthScreen,
    (authScreen) => {
        if (authScreen) {
            availabilityStore.stop();
        } else {
            availabilityStore.start(15000);
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    availabilityStore.stop();
});

const toggleDarkMode = (): void => {
    const next = !darkMode.value;
    darkMode.value = next;
    applyTheme(next);
};

const accountHref = computed(() => {
    const user = auth.value?.user;
    if (!user) {
        return '/login';
    }
    if (user.role === 'CUSTOMER') {
        return '/customer/profile';
    }
    if (user.role === 'SUPER_ADMIN' || user.role === 'ADMIN' || user.role === 'PLATFORM_STAFF') {
        return '/admin/dashboard';
    }
    if (user.role === 'RESTAURANT_OWNER' || user.role === 'RESTAURANT_STAFF') {
        return '/restaurant/dashboard';
    }
    if (user.role === 'DELIVERY_DRIVER') {
        return '/delivery/dashboard';
    }
    return '/customer/profile';
});
</script>

<template>
    <div class="relative flex min-h-dvh w-full flex-col bg-stone-50 text-stone-900 dark:bg-stone-950 dark:text-stone-100">
        <header
            v-if="!isAuthScreen && !hideHeader"
            class="sticky top-0 z-30 border-b border-stone-200 bg-white/95 px-4 pb-3 pt-[max(0.65rem,env(safe-area-inset-top))] backdrop-blur-xl dark:border-stone-800 dark:bg-stone-950/95 sm:px-6 lg:px-8"
        >
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-3">
                <Link href="/" class="flex min-w-0 items-center gap-2.5">
                    <img
                        src="/images/logo.png"
                        alt="Wasla"
                        class="h-10 w-10 shrink-0 rounded-xl object-cover shadow-sm ring-1 ring-stone-200 dark:ring-stone-700"
                    />
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold leading-none text-stone-400">التوصيل إلى</p>
                        <p class="mt-0.5 flex items-center gap-0.5 truncate text-sm font-black text-stone-900 dark:text-white">
                            <MapPin class="h-3.5 w-3.5 shrink-0 text-orange-500" />
                            <span class="truncate">جامعة برج العرب</span>
                            <ChevronLeft class="h-3.5 w-3.5 text-stone-400" />
                        </p>
                    </div>
                </Link>

                <div class="flex items-center gap-1.5">
                    <button
                        type="button"
                        aria-label="تبديل المظهر"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-stone-100 text-stone-600 dark:bg-stone-800 dark:text-amber-300"
                        @click="toggleDarkMode"
                    >
                        <Sun v-if="darkMode" class="h-4 w-4" />
                        <Moon v-else class="h-4 w-4" />
                    </button>
                    <Link
                        v-if="auth?.user"
                        href="/logout"
                        method="post"
                        as="button"
                        aria-label="تسجيل الخروج"
                        title="تسجيل الخروج"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-stone-100 text-stone-500 transition hover:bg-red-50 hover:text-red-500 dark:bg-stone-800 dark:text-stone-400 dark:hover:bg-red-950/40 dark:hover:text-red-400"
                    >
                        <LogOut class="h-4 w-4" />
                    </Link>
                    <Link
                        :href="accountHref"
                        class="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full bg-orange-500 text-sm font-black text-white"
                        aria-label="الحساب"
                    >
                        <template v-if="auth?.user">{{ auth.user.name.charAt(0) }}</template>
                        <UserIcon v-else class="h-4 w-4" />
                    </Link>
                </div>
            </div>
        </header>

        <div v-if="flash?.success" class="bg-emerald-500 px-4 py-2 text-center text-xs font-bold text-white">
            {{ flash.success }}
        </div>
        <div v-if="flash?.error" class="bg-red-500 px-4 py-2 text-center text-xs font-bold text-white">
            {{ flash.error }}
        </div>

        <main
            class="mx-auto w-full max-w-7xl flex-1"
            :class="[
                showBottomNav ? 'pb-24' : 'pb-4',
                hideHeader ? 'max-w-none' : '',
                isAuthScreen ? 'pt-[max(0.85rem,env(safe-area-inset-top))]' : '',
            ]"
        >
            <slot />
        </main>

        <AppBottomNav v-if="showBottomNav" />
        <CartDrawer />

        <a
            v-if="isAuthScreen && supportPhone"
            :href="`tel:${supportPhone}`"
            class="fixed bottom-4 left-1/2 z-10 -translate-x-1/2 text-[11px] font-bold text-stone-400"
        >
            الدعم: {{ supportPhone }}
        </a>
    </div>
</template>
