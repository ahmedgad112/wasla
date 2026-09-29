<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '../../Layouts/GuestLayout.vue';
import type { Offer, PaginatedResponse } from '../../Types';
import { Tag, Store } from '@lucide/vue';

const props = defineProps<{
    offers: PaginatedResponse<Offer>;
}>();

const items = computed(() => props.offers?.data || []);
</script>

<template>
    <GuestLayout>
        <Head title="العروض" />

        <div class="px-4 pt-4 sm:px-6 lg:px-8">
            <h1 class="text-xl font-black text-stone-900 dark:text-white md:text-2xl">العروض</h1>
            <p class="mt-0.5 text-xs text-stone-500">خصومات ووجبات توفر عليك</p>
        </div>

        <div class="grid gap-3 px-4 py-4 sm:px-6 lg:px-8 [grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr))]">
            <div v-if="items.length === 0" class="col-span-full rounded-2xl bg-white py-16 text-center dark:bg-stone-900">
                <Tag class="mx-auto mb-2 h-10 w-10 text-stone-300" />
                <p class="text-sm font-bold text-stone-700 dark:text-stone-200">مفيش عروض دلوقتي</p>
                <p class="mt-1 text-xs text-stone-500">خصم الطلاب شغال على المنيو طول الوقت</p>
                <Link
                    href="/restaurants"
                    class="mt-4 inline-flex rounded-2xl bg-orange-500 px-4 py-2 text-xs font-black text-white"
                >
                    تصفح المطاعم
                </Link>
            </div>
            <template v-else>
                <div
                    v-for="offer in items"
                    :key="offer.id"
                    class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-stone-100 dark:bg-stone-900 dark:ring-stone-800"
                >
                    <div class="flex h-28 items-end justify-between bg-gradient-to-br from-orange-500 via-amber-500 to-red-500 p-3">
                        <span class="rounded-md bg-white/20 px-2 py-0.5 text-[11px] font-black text-white">
                            خصم {{ Number(offer.discount_percentage || 0).toFixed(0) }}%
                        </span>
                        <span
                            v-if="offer.is_student_only"
                            class="rounded-md bg-stone-900/40 px-2 py-0.5 text-[10px] font-bold text-white"
                        >
                            للطلاب فقط
                        </span>
                    </div>
                    <div class="p-3">
                        <p v-if="offer.restaurant" class="mb-1 flex items-center gap-1 text-[11px] font-bold text-stone-500">
                            <Store class="h-3.5 w-3.5 text-orange-500" />
                            {{ offer.restaurant.name }}
                        </p>
                        <h3 class="text-sm font-black text-stone-900 dark:text-white">{{ offer.title }}</h3>
                        <p v-if="offer.description" class="mt-1 line-clamp-2 text-[11px] text-stone-500">{{ offer.description }}</p>
                        <div class="mt-3 flex items-center justify-between">
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-base font-black text-orange-600">
                                    {{ offer.discount_price }} ج.م
                                </span>
                                <span
                                    v-if="offer.original_price > offer.discount_price"
                                    class="text-[11px] text-stone-400 line-through"
                                >
                                    {{ offer.original_price }} ج.م
                                </span>
                            </div>
                            <Link
                                v-if="offer.restaurant"
                                :href="`/restaurants/${offer.restaurant.slug}`"
                                class="rounded-xl bg-orange-500 px-3 py-1.5 text-xs font-black text-white"
                            >
                                اطلب العرض
                            </Link>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </GuestLayout>
</template>
