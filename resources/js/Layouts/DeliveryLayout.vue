<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Bike, User, LogOut, Moon, Sun, Navigation, History } from '@lucide/vue';
import type { SharedInertiaProps } from '../Types';
import { usePermission } from '../composables/usePermission';
import { warmNavigation } from '../lib/warmNavigation';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        title?: string;
        isAvailable?: boolean;
    }>(),
    {
        isAvailable: true,
    },
);

const page = usePage<SharedInertiaProps & { driver?: { availability_status?: string } }>();
const { can } = usePermission();
const auth = computed(() => page.props.auth);
const flash = computed(() => page.props.flash);
const darkMode = ref(false);
const rememberedAvailable = ref(props.isAvailable);
const currentPath = computed(() => page.url.split('?')[0]);

watch(
    () => page.props.driver?.availability_status,
    (status) => {
        if (status) {
            rememberedAvailable.value = status === 'AVAILABLE';
        }
    },
    { immediate: true },
);

const available = computed(() => rememberedAvailable.value);

onMounted(() => {
    const isDark = localStorage.getItem('fatrna_theme') === 'dark';
    darkMode.value = isDark;
    document.documentElement.classList.toggle('dark', isDark);
    if (!('fatrna_theme' in localStorage)) {
        localStorage.setItem('fatrna_theme', 'light');
    }
});

const toggleDarkMode = (): void => {
    const next = !darkMode.value;
    darkMode.value = next;
    if (next) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('fatrna_theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('fatrna_theme', 'light');
    }
};

const isActive = (path: string): boolean => currentPath.value === path;

warmNavigation(() => {
    const hrefs: string[] = [];

    if (can('delivery.orders')) {
        hrefs.push('/delivery/dashboard', '/delivery/active-order', '/delivery/order-history');
    }

    if (can('delivery.profile')) {
        hrefs.push('/delivery/profile');
    }

    return hrefs;
});
</script>

<template>
    <div class="min-h-screen flex flex-col bg-stone-100 dark:bg-stone-950 text-stone-900 dark:text-stone-100 font-sans pb-20 sm:pb-0 transition-colors duration-200">
        <header class="sticky top-0 z-30 bg-stone-900 text-white px-4 py-3 shadow-md border-b border-stone-800">
            <div class="max-w-4xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl overflow-hidden shadow-md ring-1 ring-stone-700">
                        <img src="/images/logo.png" alt="Wasla" class="h-full w-full object-cover" />
                    </div>
                    <div>
                        <h1 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
                            <span>بوابة الطيار</span>
                            <span
                                :class="[
                                    'text-[10px] px-2 py-0.5 rounded-full font-semibold',
                                    available
                                        ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40'
                                        : 'bg-stone-800 text-stone-400',
                                ]"
                            >
                                {{ available ? 'جاهز للتوصيل' : 'غير متصل' }}
                            </span>
                        </h1>
                        <p class="text-xs text-stone-400">{{ auth.user?.name || 'كابتن التوصيل' }}</p>
                    </div>
                </div>

                <nav class="hidden sm:flex items-center gap-1 bg-stone-800/80 p-1 rounded-2xl border border-stone-700/60">
                    <Link
                        v-if="can('delivery.orders')"
                        href="/delivery/dashboard"
                        prefetch
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5',
                            isActive('/delivery/dashboard') ? 'bg-emerald-600 text-white shadow-xs' : 'text-stone-300 hover:text-white',
                        ]"
                    >
                        <Bike class="w-4 h-4" />
                        <span>الرئيسية</span>
                    </Link>
                    <Link
                        v-if="can('delivery.orders')"
                        href="/delivery/active-order"
                        prefetch
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5',
                            isActive('/delivery/active-order') ? 'bg-emerald-600 text-white shadow-xs' : 'text-stone-300 hover:text-white',
                        ]"
                    >
                        <Navigation class="w-4 h-4" />
                        <span>الطلب النشط</span>
                    </Link>
                    <Link
                        v-if="can('delivery.orders')"
                        href="/delivery/order-history"
                        prefetch
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5',
                            isActive('/delivery/order-history') ? 'bg-emerald-600 text-white shadow-xs' : 'text-stone-300 hover:text-white',
                        ]"
                    >
                        <History class="w-4 h-4" />
                        <span>سجل الطلبات</span>
                    </Link>
                    <Link
                        v-if="can('delivery.profile')"
                        href="/delivery/profile"
                        prefetch
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5',
                            isActive('/delivery/profile') ? 'bg-emerald-600 text-white shadow-xs' : 'text-stone-300 hover:text-white',
                        ]"
                    >
                        <User class="w-4 h-4" />
                        <span>حسابي</span>
                    </Link>
                </nav>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="p-2 rounded-lg bg-stone-800 text-stone-300 hover:text-white"
                        aria-label="Toggle Theme"
                        @click="toggleDarkMode"
                    >
                        <Sun v-if="darkMode" class="w-4 h-4 text-amber-400" />
                        <Moon v-else class="w-4 h-4" />
                    </button>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="p-2 rounded-lg bg-stone-800 text-stone-400 hover:text-red-400"
                        title="خروج"
                    >
                        <LogOut class="w-4 h-4" />
                    </Link>
                </div>
            </div>
        </header>

        <div v-if="flash?.success" class="bg-emerald-600 text-white text-center py-2 px-4 text-xs font-bold animate-fade-in">
            {{ flash.success }}
        </div>
        <div v-if="flash?.error" class="bg-red-600 text-white text-center py-2 px-4 text-xs font-bold animate-fade-in">
            {{ flash.error }}
        </div>

        <main class="flex-1 max-w-4xl w-full mx-auto p-4 sm:p-6">
            <slot />
        </main>

        <nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 dark:bg-stone-900/95 backdrop-blur-md border-t border-stone-200 dark:border-stone-800 py-2 px-4 flex items-center justify-around shadow-lg sm:hidden">
            <Link
                v-if="can('delivery.orders')"
                href="/delivery/dashboard"
                prefetch
                :class="[
                    'flex flex-col items-center gap-1 text-[11px] font-semibold transition',
                    isActive('/delivery/dashboard') ? 'text-emerald-600 dark:text-emerald-400' : 'text-stone-500 dark:text-stone-400',
                ]"
            >
                <Bike class="w-5 h-5" />
                <span>الرئيسية</span>
            </Link>
            <Link
                v-if="can('delivery.orders')"
                href="/delivery/active-order"
                prefetch
                :class="[
                    'flex flex-col items-center gap-1 text-[11px] font-semibold transition',
                    isActive('/delivery/active-order') ? 'text-emerald-600 dark:text-emerald-400' : 'text-stone-500 dark:text-stone-400',
                ]"
            >
                <Navigation class="w-5 h-5" />
                <span>الطلب النشط</span>
            </Link>
            <Link
                v-if="can('delivery.orders')"
                href="/delivery/order-history"
                prefetch
                :class="[
                    'flex flex-col items-center gap-1 text-[11px] font-semibold transition',
                    isActive('/delivery/order-history') ? 'text-emerald-600 dark:text-emerald-400' : 'text-stone-500 dark:text-stone-400',
                ]"
            >
                <History class="w-5 h-5" />
                <span>السجل</span>
            </Link>
            <Link
                v-if="can('delivery.profile')"
                href="/delivery/profile"
                prefetch
                :class="[
                    'flex flex-col items-center gap-1 text-[11px] font-semibold transition',
                    isActive('/delivery/profile') ? 'text-emerald-600 dark:text-emerald-400' : 'text-stone-500 dark:text-stone-400',
                ]"
            >
                <User class="w-5 h-5" />
                <span>حسابي</span>
            </Link>
        </nav>
    </div>
</template>
