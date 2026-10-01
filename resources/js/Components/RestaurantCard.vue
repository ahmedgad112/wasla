<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import type { Restaurant } from '../Types';
import { Clock, Bike, Star } from '@lucide/vue';
import { availabilityMeta } from '../lib/restaurantAvailability';
import { resolveMediaUrl } from '../lib/media';
import { useAvailabilityStore } from '../Stores/availabilityStore';
import { NAVIGATION_CACHE_FOR } from '../lib/warmNavigation';

let restaurantDetailsReady = false;

const props = defineProps<{
    restaurant: Restaurant;
    foodCount?: number;
}>();

const availabilityStore = useAvailabilityStore();
const liveRestaurant = computed(() => availabilityStore.applyLive(props.restaurant));
const discount = computed(() => Number(liveRestaurant.value.student_discount_percentage || 0));
const eta = computed(() => liveRestaurant.value.estimated_delivery_time || 25);
const fee = computed(() => Number(liveRestaurant.value.delivery_fee ?? liveRestaurant.value.delivery_base_fee ?? 0));
const isPickupOnly = computed(() => liveRestaurant.value.delivery_provider === 'PICKUP');
const feeLabel = computed(() => {
    if (isPickupOnly.value) {
        return 'استلام من المطعم';
    }

    return fee.value > 0 ? `${fee.value} ج.م` : 'توصيل مجاني';
});
const availability = computed(() => availabilityStore.resolve(props.restaurant));
const meta = computed(() => availabilityMeta(availability.value));
const imageSrc = computed(() =>
    resolveMediaUrl(liveRestaurant.value.cover_image || liveRestaurant.value.logo),
);
const logoSrc = computed(() =>
    resolveMediaUrl(liveRestaurant.value.logo || liveRestaurant.value.cover_image),
);

const etaLabel = computed(() =>
    availability.value === 'BUSY'
        ? `${eta.value + 10}-${eta.value + 25}`
        : `${eta.value}-${eta.value + 10}`,
);

const availabilityClass = computed(() => {
    if (availability.value === 'OPEN') {
        return 'text-emerald-600';
    }
    if (availability.value === 'BUSY') {
        return 'text-amber-600';
    }
    return 'text-stone-500';
});

const onImageError = (event: Event): void => {
    (event.target as HTMLImageElement).src = '/images/sandwich-foul.jpg';
};

const warmRestaurant = (): void => {
    router.prefetch(`/restaurants/${liveRestaurant.value.slug}`, {}, { cacheFor: [...NAVIGATION_CACHE_FOR] });
};

onMounted(() => {
    if (restaurantDetailsReady) {
        return;
    }

    restaurantDetailsReady = true;
    void import('../Pages/Public/RestaurantDetails.vue');
});
</script>

<template>
    <Link
        :href="`/restaurants/${liveRestaurant.slug}`"
        prefetch
        class="block transition-transform active:scale-[0.99]"
        @pointerdown="warmRestaurant"
    >
        <article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-stone-100 dark:bg-stone-900 dark:ring-stone-800">
            <div class="relative h-36 overflow-hidden bg-stone-100 dark:bg-stone-800 sm:h-40">
                <img
                    :src="imageSrc"
                    :alt="liveRestaurant.name"
                    loading="lazy"
                    decoding="async"
                    :class="['h-full w-full object-cover', availability === 'CLOSED' ? 'grayscale-[.35]' : '']"
                    @error="onImageError"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-transparent to-black/10" />

                <span
                    v-if="discount > 0"
                    class="absolute top-2.5 right-2.5 rounded-md bg-emerald-500 px-2 py-0.5 text-[11px] font-black text-white shadow"
                >
                    خصم طلاب {{ discount }}%
                </span>

                <span class="absolute top-2.5 left-2.5 inline-flex items-center gap-1 rounded-md bg-white/95 px-1.5 py-0.5 text-[10px] font-bold text-stone-800 shadow-sm">
                    <Clock class="h-3 w-3 text-orange-500" />
                    {{ etaLabel }} د
                </span>

                <div
                    v-if="availability !== 'OPEN'"
                    class="absolute inset-0 flex items-center justify-center bg-black/40"
                >
                    <span
                        :class="[
                            'rounded-full px-3 py-1 text-xs font-black text-white shadow',
                            availability === 'BUSY' ? 'bg-amber-600' : 'bg-stone-900/85',
                        ]"
                    >
                        {{ meta.label }}
                    </span>
                </div>

                <div class="absolute -bottom-5 right-3 h-11 w-11 overflow-hidden rounded-xl border-2 border-white bg-white shadow-md dark:border-stone-900">
                    <img
                        :src="logoSrc"
                        alt=""
                        loading="lazy"
                        decoding="async"
                        class="h-full w-full object-cover"
                        @error="onImageError"
                    />
                </div>
            </div>

            <div class="px-3 pb-3 pt-7">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="truncate text-[15px] font-black text-stone-900 dark:text-white">
                        {{ liveRestaurant.name }}
                    </h3>
                    <span class="inline-flex shrink-0 items-center gap-0.5 rounded-md bg-amber-50 px-1.5 py-0.5 text-[10px] font-black text-amber-700 dark:bg-amber-950/50 dark:text-amber-300">
                        <Star class="h-3 w-3 fill-amber-400 text-amber-400" />
                        4.8
                    </span>
                </div>

                <p class="mt-0.5 line-clamp-1 text-[11px] text-stone-500 dark:text-stone-400">
                    {{ liveRestaurant.description || liveRestaurant.address || 'وجبات وإفطار للحرم الجامعي' }}
                </p>

                <div class="mt-2 flex items-center gap-2 text-[11px] font-semibold text-stone-500 dark:text-stone-400">
                    <span class="inline-flex items-center gap-1">
                        <Bike class="h-3.5 w-3.5 text-orange-500" />
                        {{ feeLabel }}
                    </span>
                    <span class="text-stone-300">•</span>
                    <span v-if="foodCount !== undefined">{{ foodCount }} صنف</span>
                    <span class="text-stone-300">•</span>
                    <span :class="availabilityClass">
                        {{ meta.short }}
                    </span>
                </div>
            </div>
        </article>
    </Link>
</template>
