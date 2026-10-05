<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import RestaurantCard from '../../Components/RestaurantCard.vue';
import type { Restaurant, PaginatedResponse } from '../../Types';
import { Search, Store } from '@lucide/vue';
import { useUserLocation } from '../../composables/useUserLocation';

const props = defineProps<{
    restaurants: PaginatedResponse<Restaurant> | Restaurant[];
}>();

const search = ref('');
const { place, sortByUserDistance } = useUserLocation();

const items = computed(() =>
    Array.isArray(props.restaurants) ? props.restaurants : props.restaurants?.data || [],
);

const filtered = computed(() => {
    const matched = items.value.filter(
        (restaurant) =>
            restaurant.name.toLowerCase().includes(search.value.toLowerCase()) ||
            (restaurant.description && restaurant.description.toLowerCase().includes(search.value.toLowerCase())) ||
            (restaurant.address && restaurant.address.toLowerCase().includes(search.value.toLowerCase())),
    );

    return place.value ? sortByUserDistance(matched) : matched;
});
</script>

<template>
        <Head title="المطاعم" />

        <div class="px-4 pt-4 pb-2 sm:px-6 lg:px-8">
            <h1 class="text-xl font-black text-stone-900 dark:text-white md:text-2xl">المطاعم</h1>
            <p class="mt-0.5 text-xs text-stone-500">
                {{ place ? `الأقرب إلى ${place.label} أولاً` : 'اختار مطعمك واطلب في دقايق' }}
            </p>
            <div class="relative mt-3 max-w-2xl">
                <Search class="absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
                <input
                    v-model="search"
                    type="search"
                    placeholder="ابحث باسم المطعم..."
                    class="w-full rounded-2xl border-0 bg-white py-3 pr-10 pl-4 text-sm font-medium shadow-sm ring-1 ring-stone-100 placeholder:text-stone-400 focus:ring-2 focus:ring-orange-500 dark:bg-stone-900 dark:ring-stone-800"
                />
            </div>
        </div>

        <div class="grid gap-3 px-4 pb-4 pt-2 sm:px-6 lg:px-8 [grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr))]">
            <div v-if="filtered.length === 0" class="col-span-full rounded-2xl bg-white py-16 text-center dark:bg-stone-900">
                <Store class="mx-auto mb-2 h-10 w-10 text-stone-300" />
                <p class="text-sm font-bold text-stone-600">مفيش مطاعم مطابقة</p>
            </div>
            <template v-else>
                <RestaurantCard
                    v-for="restaurant in filtered"
                    :key="restaurant.id"
                    :restaurant="restaurant"
                />
            </template>
        </div>
</template>
