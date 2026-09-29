<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import GuestLayout from '../../Layouts/GuestLayout.vue';
import RestaurantCard from '../../Components/RestaurantCard.vue';
import type { Restaurant, Offer, SharedInertiaProps } from '../../Types';
import { useAvailabilityStore } from '../../Stores/availabilityStore';
import {
    Search,
    Percent,
    GraduationCap,
    Flame,
    Store,
    Crown,
    ChevronLeft,
} from '@lucide/vue';

interface LeaderboardRanking {
    userId: number;
    userName: string;
    badge: string;
    totalOrders: number;
    totalItems: number;
}

const props = withDefaults(
    defineProps<{
        restaurants?: (Restaurant & { menu_items_count?: number; offers_count?: number })[];
        featuredRestaurants?: (Restaurant & { menu_items_count?: number })[];
        totalDishes?: number;
        activeOffers?: Offer[];
        leaderboard?: {
            rankings: LeaderboardRanking[];
            kingOfBreakfast: LeaderboardRanking | null;
            totalOrdersInSystem: number;
            totalItemsInSystem: number;
        };
    }>(),
    {
        restaurants: () => [],
        featuredRestaurants: () => [],
        activeOffers: () => [],
    },
);

const FILTERS = [
    { id: 'all', label: 'الكل' },
    { id: 'student', label: 'خصم طلاب' },
    { id: 'offers', label: 'عروض' },
    { id: 'open', label: 'مفتوح دلوقتي' },
];

const page = usePage<SharedInertiaProps>();
const auth = computed(() => page.props.auth);
const searchQuery = ref('');
const filter = ref('all');
const availabilityStore = useAvailabilityStore();

const restaurantList = computed(() => {
    if (Array.isArray(props.restaurants) && props.restaurants.length > 0) {
        return props.restaurants;
    }
    if (Array.isArray(props.featuredRestaurants)) {
        return props.featuredRestaurants;
    }
    return [];
});

const filteredRestaurants = computed(() =>
    restaurantList.value.filter((restaurant) => {
        const haystack = `${restaurant.name} ${restaurant.description || ''} ${restaurant.address || ''}`.toLowerCase();
        const matchesSearch = haystack.includes(searchQuery.value.toLowerCase());
        if (!matchesSearch) {
            return false;
        }
        if (filter.value === 'student') {
            return Number(restaurant.student_discount_percentage || 0) > 0;
        }
        if (filter.value === 'offers') {
            return Number(restaurant.offers_count || 0) > 0;
        }
        if (filter.value === 'open') {
            return ['OPEN', 'BUSY'].includes(availabilityStore.resolve(restaurant));
        }
        return true;
    }),
);

const firstName = computed(() => auth.value?.user?.name?.split(' ')[0]);
const hour = new Date().getHours();
const greeting = hour < 12 ? 'صباح الخير' : hour < 18 ? 'مساء الخير' : 'أهلاً بيك';
</script>

<template>
    <GuestLayout>
        <Head title="اطلب من مطاعم الجامعة" />

        <div class="space-y-5 pb-4">
            <section class="px-4 pt-4 sm:px-6 lg:px-8">
                <p class="text-xs font-bold text-orange-600">{{ greeting }}{{ firstName ? `، ${firstName}` : '' }}</p>
                <h1 class="mt-0.5 text-xl font-black text-stone-900 dark:text-white md:text-2xl">
                    هتطلب إيه النهاردة؟
                </h1>

                <div class="relative mt-3 max-w-2xl">
                    <Search class="absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
                    <input
                        v-model="searchQuery"
                        type="search"
                        placeholder="ابحث عن مطعم أو وجبة..."
                        class="w-full rounded-2xl border-0 bg-white py-3 pr-10 pl-4 text-sm font-medium shadow-sm ring-1 ring-stone-100 placeholder:text-stone-400 focus:ring-2 focus:ring-orange-500 dark:bg-stone-900 dark:ring-stone-800"
                    />
                </div>
            </section>

            <section class="chip-scroll px-4 sm:px-6 lg:px-8">
                <button
                    v-for="item in FILTERS"
                    :key="item.id"
                    type="button"
                    class="shrink-0 rounded-full px-3.5 py-1.5 text-xs font-black transition"
                    :class="
                        filter === item.id
                            ? 'bg-stone-900 text-white dark:bg-white dark:text-stone-900'
                            : 'bg-white text-stone-600 ring-1 ring-stone-200 dark:bg-stone-900 dark:text-stone-300 dark:ring-stone-800'
                    "
                    @click="filter = item.id"
                >
                    {{ item.label }}
                </button>
            </section>

            <section class="px-4 sm:px-6 lg:px-8">
                <Link
                    :href="auth?.user ? '/customer/profile' : '/register'"
                    class="flex items-center justify-between rounded-2xl bg-gradient-to-l from-orange-500 to-amber-500 px-4 py-3.5 text-white shadow-lg shadow-orange-500/20"
                >
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-white/20">
                            <GraduationCap class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-sm font-black">خصم طلاب الجامعة</p>
                            <p class="text-[11px] text-orange-50">ارفع الكارنيه ووفّر لحد 25%</p>
                        </div>
                    </div>
                    <ChevronLeft class="h-5 w-5" />
                </Link>
            </section>

            <section v-if="activeOffers.length > 0" class="px-4 sm:px-6 lg:px-8">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="flex items-center gap-1.5 text-sm font-black text-stone-900 dark:text-white">
                        <Percent class="h-4 w-4 text-orange-500" />
                        عروض النهاردة
                    </h2>
                    <Link href="/offers" class="shrink-0 text-[11px] font-bold text-orange-600">
                        الكل
                    </Link>
                </div>
                <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr))]">
                    <Link
                        v-for="offer in activeOffers.slice(0, 6)"
                        :key="offer.id"
                        :href="offer.restaurant ? `/restaurants/${offer.restaurant.slug}` : '/offers'"
                        class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-stone-100 dark:bg-stone-900 dark:ring-stone-800"
                    >
                        <div class="flex h-24 items-end bg-gradient-to-br from-orange-500 via-amber-500 to-red-500 p-3">
                            <span class="rounded-md bg-white/20 px-2 py-0.5 text-[11px] font-black text-white">
                                خصم {{ Number(offer.discount_percentage || 0).toFixed(0) }}%
                            </span>
                        </div>
                        <div class="p-3">
                            <p class="truncate text-sm font-black text-stone-900 dark:text-white">
                                {{ offer.title }}
                            </p>
                            <p class="truncate text-[11px] text-stone-500">
                                {{ offer.restaurant?.name || 'عرض حصري' }}
                            </p>
                        </div>
                    </Link>
                </div>
            </section>

            <section class="px-4 sm:px-6 lg:px-8">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="flex items-center gap-1.5 text-sm font-black text-stone-900 dark:text-white">
                        <Store class="h-4 w-4 text-orange-500" />
                        مطاعم قريبة منك
                    </h2>
                    <Link href="/restaurants" class="shrink-0 text-[11px] font-bold text-orange-600">
                        عرض الكل
                    </Link>
                </div>

                <div v-if="filteredRestaurants.length === 0" class="rounded-2xl bg-white py-12 text-center dark:bg-stone-900">
                    <Store class="mx-auto mb-2 h-10 w-10 text-stone-300" />
                    <p class="text-sm font-bold text-stone-600">مفيش مطاعم مطابقة</p>
                </div>
                <div v-else class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr))]">
                    <RestaurantCard
                        v-for="restaurant in filteredRestaurants"
                        :key="restaurant.id"
                        :restaurant="restaurant"
                        :food-count="restaurant.menu_items_count"
                    />
                </div>
            </section>

            <section class="px-4 sm:px-6 lg:px-8">
                <Link
                    href="/leaderboard"
                    class="flex items-center justify-between rounded-2xl bg-stone-900 px-4 py-3.5 text-white"
                >
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-400 text-stone-900">
                            <Crown v-if="leaderboard?.kingOfBreakfast" class="h-5 w-5" />
                            <Flame v-else class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-sm font-black">الأكثر طلباً</p>
                            <p class="text-[11px] text-stone-300">
                                {{
                                    leaderboard?.kingOfBreakfast
                                        ? `المتصدر: ${leaderboard.kingOfBreakfast.userName}`
                                        : 'ادخل السباق واطلب دلوقتي'
                                }}
                            </p>
                        </div>
                    </div>
                    <ChevronLeft class="h-5 w-5 text-stone-400" />
                </Link>
            </section>
        </div>
    </GuestLayout>
</template>
