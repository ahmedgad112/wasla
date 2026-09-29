<script setup lang="ts">
import { computed, ref } from 'vue';
import type { MenuItem, Restaurant } from '../Types';
import { useCartStore } from '../Stores/cartStore';
import { Plus, Check } from '@lucide/vue';
import { availabilityMeta } from '../lib/restaurantAvailability';
import { resolveMediaUrl } from '../lib/media';
import { useAvailabilityStore } from '../Stores/availabilityStore';

const props = defineProps<{
    food: MenuItem;
    restaurant: Restaurant;
    studentDiscountPercent?: number;
    onCustomize?: () => void;
    onRestaurantConflict?: () => void;
}>();

const isAdded = ref(false);
const cart = useCartStore();
const availabilityStore = useAvailabilityStore();
const liveRestaurant = computed(() => availabilityStore.applyLive(props.restaurant));
const basePrice = computed(() => Number(props.food.effective_price ?? props.food.discount_price ?? props.food.price));
const hasOptions = computed(() => Boolean(props.food.options && props.food.options.length > 0));
const imageSrc = computed(() => resolveMediaUrl(props.food.image));
const canOrder = computed(() => availabilityMeta(availabilityStore.resolve(props.restaurant)).canOrder);
const isUnavailable = computed(() => !props.food.is_available || !canOrder.value);

const handleAdd = (event: Event): void => {
    event.preventDefault();
    event.stopPropagation();
    if (isUnavailable.value) {
        return;
    }
    if (hasOptions.value && props.onCustomize) {
        props.onCustomize();
        return;
    }
    const added = cart.addItem(props.food, liveRestaurant.value, 1, [], [], '');
    if (!added) {
        props.onRestaurantConflict?.();
        return;
    }
    isAdded.value = true;
    window.setTimeout(() => {
        isAdded.value = false;
    }, 1200);
};

const handleCardClick = (): void => {
    if (props.onCustomize) {
        props.onCustomize();
        return;
    }
};

const onImageError = (event: Event): void => {
    (event.target as HTMLImageElement).src = '/images/sandwich-foul.jpg';
};
</script>

<template>
    <div
        class="flex cursor-pointer gap-3 border-b border-stone-100 bg-white px-4 py-3.5 active:bg-stone-50 dark:border-stone-800 dark:bg-stone-950 dark:active:bg-stone-900"
        role="button"
        @click="handleCardClick"
    >
        <div class="min-w-0 flex-1">
            <h3 class="text-[15px] font-black leading-snug text-stone-900 dark:text-white">
                {{ food.name }}
            </h3>
            <p
                v-if="food.description"
                class="mt-1 line-clamp-2 text-[11px] leading-relaxed text-stone-500 dark:text-stone-400"
            >
                {{ food.description }}
            </p>
            <div class="mt-2 flex items-center gap-2">
                <span class="text-sm font-black text-stone-900 dark:text-white">
                    {{ basePrice }} ج.م
                </span>
                <span
                    v-if="food.discount_price && food.discount_price < food.price"
                    class="text-[11px] text-stone-400 line-through"
                >
                    {{ food.price }} ج.م
                </span>
                <span
                    v-if="hasOptions"
                    class="rounded-md bg-orange-50 px-1.5 py-0.5 text-[10px] font-bold text-orange-600 dark:bg-orange-950/40 dark:text-orange-300"
                >
                    اختيارات
                </span>
            </div>
        </div>

        <div class="relative h-[92px] w-[92px] shrink-0">
            <img
                :src="imageSrc"
                :alt="food.name"
                class="h-full w-full rounded-2xl object-cover"
                loading="lazy"
                @error="onImageError"
            />
            <button
                v-if="!isUnavailable"
                type="button"
                :class="[
                    'absolute -bottom-1.5 -left-1.5 flex h-8 w-8 items-center justify-center rounded-full shadow-md transition active:scale-90',
                    isAdded
                        ? 'bg-emerald-500 text-white'
                        : 'bg-white text-orange-600 ring-1 ring-stone-200 dark:bg-stone-900 dark:ring-stone-700',
                ]"
                :aria-label="hasOptions ? 'تخصيص الصنف' : 'أضف للسلة'"
                @click="handleAdd"
            >
                <Check v-if="isAdded" class="h-4 w-4" />
                <Plus v-else class="h-4 w-4 stroke-[2.6]" />
            </button>
        </div>
    </div>
</template>
