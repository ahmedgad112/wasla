<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '../../Layouts/GuestLayout.vue';
import type { Category, MenuItem, MenuItemAddon, Offer, Restaurant } from '../../Types';
import { useCartStore } from '../../Stores/cartStore';
import CategoryIcon from '../../Components/CategoryIcon.vue';
import FoodCard from '../../Components/FoodCard.vue';
import { availabilityMeta } from '../../lib/restaurantAvailability';
import { resolveMediaUrl } from '../../lib/media';
import { useAvailabilityStore } from '../../Stores/availabilityStore';
import {
    MapPin,
    GraduationCap,
    Search,
    Sparkles,
    ArrowRight,
    ShoppingBag,
    X,
    Check,
    AlertTriangle,
    Plus,
    Minus,
    Clock,
    Star,
    Bike,
} from '@lucide/vue';

const props = defineProps<{
    restaurant: Restaurant;
}>();

const selectedCategoryId = ref<number | null>(null);
const searchQuery = ref('');
const selectedItem = ref<MenuItem | null>(null);
const modalQuantity = ref(1);
const selectedOptions = ref<{ [optionId: number]: { valueId: number; name: string; value: string; price: number } }>({});
const selectedAddons = ref<MenuItemAddon[]>([]);
const itemNotes = ref('');
const showConflictModal = ref(false);
const pendingItemToAdd = ref<{
    item: MenuItem;
    quantity: number;
    options: { id?: number; optionName: string; valueName: string; price: number }[];
    addons: MenuItemAddon[];
    notes: string;
} | null>(null);

const cart = useCartStore();
const availabilityStore = useAvailabilityStore();
const categoryTabsEl = ref<HTMLElement | null>(null);
const isProgrammaticScroll = ref(false);
let sectionObserver: IntersectionObserver | null = null;

const liveRestaurant = computed(() => {
    const restaurant = props.restaurant;
    if (!restaurant?.id) {
        return restaurant;
    }

    return availabilityStore.applyLive(restaurant);
});
const availability = computed(() => availabilityStore.resolve(props.restaurant));
const meta = computed(() => availabilityMeta(availability.value));
const canOrder = computed(() => meta.value.canOrder);
const categories = computed(() => liveRestaurant.value.categories || []);

const categoriesWithItems = computed(() =>
    categories.value.filter((cat) => cat.menu_items && cat.menu_items.length > 0),
);

const offers = computed((): Offer[] => liveRestaurant.value.offers ?? []);

const query = computed(() => searchQuery.value.trim().toLowerCase());

const matchesQuery = (item: MenuItem): boolean => {
    if (!query.value) {
        return true;
    }

    return (
        item.name.toLowerCase().includes(query.value)
        || Boolean(item.description && item.description.toLowerCase().includes(query.value))
    );
};

const visibleSections = computed((): Category[] => {
    return categoriesWithItems.value
        .map((cat) => ({
            ...cat,
            menu_items: (cat.menu_items ?? []).filter(matchesQuery),
        }))
        .filter((cat) => (cat.menu_items ?? []).length > 0);
});

const currentModalUnitPrice = computed(() =>
    selectedItem.value
        ? Number(selectedItem.value.effective_price ?? selectedItem.value.discount_price ?? selectedItem.value.price) +
          Object.values(selectedOptions.value).reduce((s, o) => s + o.price, 0) +
          selectedAddons.value.reduce((s, a) => s + Number(a.price || 0), 0)
        : 0,
);

const cartCount = computed(() => cart.getItemCount);
const cartTotal = computed(() => cart.getTotal);
const coverSrc = computed(() =>
    resolveMediaUrl(liveRestaurant.value.cover_image || liveRestaurant.value.logo),
);
const logoSrc = computed(() =>
    resolveMediaUrl(liveRestaurant.value.logo || liveRestaurant.value.cover_image),
);
const eta = computed(() => liveRestaurant.value.estimated_delivery_time || 25);
const fee = computed(() => Number(liveRestaurant.value.delivery_fee ?? liveRestaurant.value.delivery_base_fee ?? 0));
const isPickupOnly = computed(() => liveRestaurant.value.delivery_provider === 'PICKUP');
const etaLabel = computed(() =>
    availability.value === 'BUSY' ? `${eta.value + 10}-${eta.value + 25}` : `${eta.value}-${eta.value + 10}`,
);
const feeLabel = computed(() => {
    if (isPickupOnly.value) {
        return 'استلام من المطعم';
    }

    return fee.value > 0 ? `${fee.value} ج.م` : 'توصيل مجاني';
});
const selectedItemImage = computed(() =>
    selectedItem.value ? resolveMediaUrl(selectedItem.value.image) : '',
);

const onImageError = (e: Event): void => {
    (e.target as HTMLImageElement).src = '/images/sandwich-foul.jpg';
};

const openItemModal = (item: MenuItem): void => {
    selectedItem.value = item;
    modalQuantity.value = 1;
    itemNotes.value = '';
    selectedAddons.value = [];
    const initialOpts: { [key: number]: { valueId: number; name: string; value: string; price: number } } = {};
    if (item.options) {
        item.options.forEach((opt) => {
            if (opt.values && opt.values.length > 0) {
                initialOpts[opt.id] = {
                    valueId: opt.values[0].id,
                    name: opt.name,
                    value: opt.values[0].name,
                    price: Number(opt.values[0].price || 0),
                };
            }
        });
    }
    selectedOptions.value = initialOpts;
};

const handleAddToCartFromModal = (): void => {
    if (!selectedItem.value) {
        return;
    }
    const optionsArray = Object.values(selectedOptions.value).map((o) => ({
        id: o.valueId,
        optionName: o.name,
        valueName: o.value,
        price: o.price,
    }));
    const success = cart.addItem(
        selectedItem.value,
        liveRestaurant.value,
        modalQuantity.value,
        optionsArray,
        selectedAddons.value,
        itemNotes.value,
    );
    if (!success) {
        pendingItemToAdd.value = {
            item: selectedItem.value,
            quantity: modalQuantity.value,
            options: optionsArray,
            addons: selectedAddons.value,
            notes: itemNotes.value,
        };
        showConflictModal.value = true;
    }
    selectedItem.value = null;
};

const confirmClearAndAdd = (): void => {
    if (pendingItemToAdd.value) {
        cart.clearCart();
        cart.addItem(
            pendingItemToAdd.value.item,
            liveRestaurant.value,
            pendingItemToAdd.value.quantity,
            pendingItemToAdd.value.options,
            pendingItemToAdd.value.addons,
            pendingItemToAdd.value.notes,
        );
        pendingItemToAdd.value = null;
    }
    showConflictModal.value = false;
};

const toggleAddon = (addon: MenuItemAddon): void => {
    if (selectedAddons.value.some((a) => a.id === addon.id)) {
        selectedAddons.value = selectedAddons.value.filter((a) => a.id !== addon.id);
    } else {
        selectedAddons.value = [...selectedAddons.value, addon];
    }
};

const selectOptionValue = (opt: { id: number; name: string }, val: { id: number; name: string; price: number }): void => {
    selectedOptions.value = {
        ...selectedOptions.value,
        [opt.id]: { valueId: val.id, name: opt.name, value: val.name, price: Number(val.price) },
    };
};

const handleRestaurantConflict = (food: MenuItem): void => {
    pendingItemToAdd.value = { item: food, quantity: 1, options: [], addons: [], notes: '' };
    showConflictModal.value = true;
};

const clearSearch = (): void => {
    searchQuery.value = '';
};

const scrollCategoryTabIntoView = (categoryId: number): void => {
    const tab = categoryTabsEl.value?.querySelector<HTMLElement>(`[data-cat-tab="${categoryId}"]`);
    tab?.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
};

const scrollToCategory = (categoryId: number): void => {
    selectedCategoryId.value = categoryId;
    isProgrammaticScroll.value = true;
    scrollCategoryTabIntoView(categoryId);
    const section = document.getElementById(`menu-cat-${categoryId}`);
    section?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    window.setTimeout(() => {
        isProgrammaticScroll.value = false;
    }, 700);
};

const disconnectObserver = (): void => {
    sectionObserver?.disconnect();
    sectionObserver = null;
};

const observeSections = (): void => {
    disconnectObserver();

    if (query.value || visibleSections.value.length === 0) {
        return;
    }

    sectionObserver = new IntersectionObserver(
        (entries) => {
            if (isProgrammaticScroll.value) {
                return;
            }

            const visible = entries
                .filter((entry) => entry.isIntersecting)
                .sort((a, b) => b.intersectionRatio - a.intersectionRatio);

            const top = visible[0];
            if (!top?.target.id.startsWith('menu-cat-')) {
                return;
            }

            const id = Number(top.target.id.replace('menu-cat-', ''));
            if (!Number.isNaN(id) && selectedCategoryId.value !== id) {
                selectedCategoryId.value = id;
                scrollCategoryTabIntoView(id);
            }
        },
        { rootMargin: '-140px 0px -55% 0px', threshold: [0.08, 0.2, 0.4] },
    );

    visibleSections.value.forEach((cat) => {
        const el = document.getElementById(`menu-cat-${cat.id}`);
        if (el) {
            sectionObserver?.observe(el);
        }
    });
};

watch(
    () => visibleSections.value.map((cat) => cat.id).join(','),
    async () => {
        if (!selectedCategoryId.value && visibleSections.value[0]) {
            selectedCategoryId.value = visibleSections.value[0].id;
        }
        await nextTick();
        observeSections();
    },
);

onMounted(async () => {
    if (visibleSections.value[0]) {
        selectedCategoryId.value = visibleSections.value[0].id;
    }
    await nextTick();
    observeSections();
});

onBeforeUnmount(() => {
    disconnectObserver();
});
</script>

<template>
    <GuestLayout hide-header>
        <Head :title="`${liveRestaurant.name} — اطلب أونلاين`" />

        <div class="relative mx-auto w-full max-w-3xl bg-white dark:bg-stone-950">
            <div class="relative h-52 sm:h-60">
                <img
                    :src="coverSrc"
                    :alt="liveRestaurant.name"
                    class="h-full w-full object-cover"
                    :class="availability === 'CLOSED' ? 'grayscale-[.35]' : ''"
                    @error="onImageError"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/55 via-black/15 to-black/25" />

                <Link
                    href="/restaurants"
                    class="absolute right-3 top-[max(0.75rem,env(safe-area-inset-top))] grid h-10 w-10 place-items-center rounded-full bg-white/95 text-stone-800 shadow-md"
                    aria-label="رجوع"
                >
                    <ArrowRight class="h-5 w-5" />
                </Link>

                <div
                    v-if="availability !== 'OPEN'"
                    class="absolute inset-x-0 bottom-14 flex justify-center"
                >
                    <span
                        class="rounded-full px-3 py-1 text-xs font-black text-white shadow"
                        :class="availability === 'BUSY' ? 'bg-amber-600' : 'bg-stone-900/85'"
                    >
                        {{ meta.label }}
                    </span>
                </div>
            </div>

            <div class="relative -mt-8 rounded-t-3xl bg-white px-4 pb-4 pt-5 shadow-[0_-8px_24px_rgba(0,0,0,.06)] dark:bg-stone-950">
                <div
                    class="absolute -top-8 right-4 h-16 w-16 overflow-hidden rounded-2xl border-4 border-white bg-white shadow-lg dark:border-stone-950"
                >
                    <img :src="logoSrc" :alt="liveRestaurant.name" class="h-full w-full object-cover" @error="onImageError" />
                </div>

                <div class="flex items-start justify-between gap-3 pr-[4.75rem]">
                    <div class="min-w-0">
                        <h1 class="text-xl font-black leading-tight text-stone-900 dark:text-white">
                            {{ liveRestaurant.name }}
                        </h1>
                        <p class="mt-1 line-clamp-2 text-xs text-stone-500">
                            {{ liveRestaurant.description || liveRestaurant.address || 'وجبات للحرم الجامعي' }}
                        </p>
                    </div>
                    <span
                        class="inline-flex shrink-0 items-center gap-0.5 rounded-lg bg-amber-50 px-2 py-1 text-xs font-black text-amber-700 dark:bg-amber-950/40 dark:text-amber-300"
                    >
                        <Star class="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
                        4.8
                    </span>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[12px] font-semibold text-stone-600 dark:text-stone-400">
                    <span class="inline-flex items-center gap-1">
                        <Clock class="h-3.5 w-3.5 text-orange-500" />
                        {{ etaLabel }} د
                    </span>
                    <span class="text-stone-300">•</span>
                    <span class="inline-flex items-center gap-1">
                        <Bike class="h-3.5 w-3.5 text-orange-500" />
                        {{ feeLabel }}
                    </span>
                    <template v-if="liveRestaurant.address">
                        <span class="text-stone-300">•</span>
                        <span class="inline-flex max-w-[180px] items-center gap-1 truncate">
                            <MapPin class="h-3.5 w-3.5 shrink-0 text-orange-500" />
                            {{ liveRestaurant.address }}
                        </span>
                    </template>
                </div>

                <div
                    v-if="liveRestaurant.student_discount_percentage > 0"
                    class="mt-3 flex items-center gap-2 rounded-xl bg-emerald-50 px-3 py-2 text-[11px] font-bold text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200"
                >
                    <GraduationCap class="h-4 w-4 shrink-0" />
                    خصم طلاب {{ liveRestaurant.student_discount_percentage }}% على المنيو
                </div>
            </div>

            <div v-if="offers.length > 0" class="chip-scroll px-4 pb-3">
                <div
                    v-for="offer in offers"
                    :key="offer.id"
                    class="flex min-w-[220px] max-w-[260px] shrink-0 items-center gap-2 rounded-2xl bg-orange-50 px-3 py-2.5 ring-1 ring-orange-100 dark:bg-orange-950/30 dark:ring-orange-900/40"
                >
                    <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-orange-500 text-white">
                        <Sparkles class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-xs font-black text-stone-900 dark:text-white">{{ offer.title }}</p>
                        <p class="truncate text-[11px] font-semibold text-orange-700 dark:text-orange-300">
                            {{ offer.discount_percentage }}% خصم
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="availability === 'CLOSED'"
                class="mx-4 mb-3 flex items-center gap-2 rounded-2xl bg-stone-100 px-3 py-2.5 text-xs font-bold text-stone-600 dark:bg-stone-900"
            >
                <AlertTriangle class="h-4 w-4 shrink-0" />
                المطعم مغلق دلوقتي ومش بياخد طلبات
            </div>
            <div
                v-if="availability === 'BUSY'"
                class="mx-4 mb-3 flex items-center gap-2 rounded-2xl bg-amber-50 px-3 py-2.5 text-xs font-bold text-amber-700 dark:bg-amber-950/30"
            >
                <AlertTriangle class="h-4 w-4 shrink-0" />
                المطعم مشغول — التوصيل ممكن يتأخر شوية
            </div>

            <div
                class="sticky top-0 z-20 space-y-2 border-b border-stone-100 bg-white/95 px-4 py-2 backdrop-blur-xl dark:border-stone-800 dark:bg-stone-950/95"
            >
                <div class="relative">
                    <Search class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-stone-400" />
                    <input
                        v-model="searchQuery"
                        type="search"
                        :placeholder="`دور في منيو ${liveRestaurant.name}...`"
                        class="w-full rounded-xl border-0 bg-stone-50 py-2.5 pr-9 pl-3 text-xs font-medium ring-1 ring-stone-100 focus:ring-2 focus:ring-orange-500 dark:bg-stone-900 dark:ring-stone-800"
                    />
                </div>
                <div ref="categoryTabsEl" class="chip-scroll">
                    <button
                        v-for="cat in categoriesWithItems"
                        :key="cat.id"
                        type="button"
                        :data-cat-tab="cat.id"
                        class="inline-flex shrink-0 items-center gap-1 rounded-full px-3 py-1.5 text-[11px] font-black"
                        :class="
                            selectedCategoryId === cat.id
                                ? 'bg-stone-900 text-white dark:bg-white dark:text-stone-900'
                                : 'bg-stone-50 text-stone-600 ring-1 ring-stone-200 dark:bg-stone-900 dark:text-stone-300 dark:ring-stone-800'
                        "
                        @click="scrollToCategory(cat.id)"
                    >
                        <CategoryIcon :slug="cat.slug" :name="cat.name" class="h-3.5 w-3.5" />
                        {{ cat.name }}
                    </button>
                </div>
            </div>

            <div v-if="visibleSections.length === 0" class="px-4 py-16 text-center">
                <Search class="mx-auto mb-2 h-8 w-8 text-stone-300" />
                <p class="text-sm font-bold text-stone-600">مفيش أصناف مطابقة</p>
                <button
                    type="button"
                    class="mt-3 rounded-xl bg-orange-500 px-4 py-2 text-xs font-black text-white"
                    @click="clearSearch"
                >
                    مسح البحث
                </button>
            </div>
            <div v-else :class="cartCount > 0 ? 'pb-20' : 'pb-4'">
                <section
                    v-for="cat in visibleSections"
                    :id="`menu-cat-${cat.id}`"
                    :key="cat.id"
                    class="scroll-mt-[7.5rem]"
                >
                    <div class="sticky top-[6.6rem] z-10 bg-stone-50 px-4 py-2.5 dark:bg-stone-900">
                        <h2 class="text-sm font-black text-stone-900 dark:text-white">{{ cat.name }}</h2>
                    </div>
                    <FoodCard
                        v-for="food in cat.menu_items"
                        :key="food.id"
                        :food="food"
                        :restaurant="liveRestaurant"
                        :student-discount-percent="liveRestaurant.student_discount_percentage"
                        :on-customize="() => openItemModal(food)"
                        :on-restaurant-conflict="() => handleRestaurantConflict(food)"
                    />
                </section>
            </div>

            <div
                v-if="cartCount > 0"
                class="sticky bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-20 px-4 pb-3 pt-2"
            >
                <button
                    type="button"
                    class="flex w-full items-center justify-between rounded-2xl bg-stone-900 px-4 py-3.5 text-white shadow-xl shadow-stone-900/25 dark:bg-orange-500"
                    @click="cart.openCart()"
                >
                    <span class="flex items-center gap-2 text-sm font-black">
                        <ShoppingBag class="h-4 w-4" />
                        عرض السلة • {{ cartCount }}
                    </span>
                    <span class="text-sm font-black">{{ cartTotal.toFixed(0) }} ج.م</span>
                </button>
            </div>

            <div v-if="selectedItem" class="fixed inset-0 z-50 flex items-end bg-black/60">
                <div class="max-h-[90%] w-full overflow-y-auto rounded-t-3xl bg-white dark:bg-stone-900">
                    <div class="relative h-40 w-full">
                        <img
                            :src="selectedItemImage"
                            :alt="selectedItem.name"
                            class="h-full w-full object-cover"
                            @error="onImageError"
                        />
                        <button
                            type="button"
                            class="absolute left-3 top-3 grid h-8 w-8 place-items-center rounded-full bg-white/95 text-stone-600 shadow"
                            @click="selectedItem = null"
                        >
                            <X class="h-4 w-4" />
                        </button>
                    </div>
                    <div class="p-4">
                        <div class="mb-4">
                            <h3 class="text-lg font-black text-stone-900 dark:text-white">{{ selectedItem.name }}</h3>
                            <p v-if="selectedItem.description" class="mt-1 text-xs leading-relaxed text-stone-500">
                                {{ selectedItem.description }}
                            </p>
                        </div>

                        <div v-for="opt in selectedItem.options" :key="opt.id" class="mb-4 space-y-2">
                            <div class="flex items-center justify-between text-xs font-bold">
                                <span>{{ opt.name }}</span>
                                <span v-if="opt.is_required" class="text-[10px] text-red-500">مطلوب</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    v-for="val in opt.values"
                                    :key="val.id"
                                    type="button"
                                    class="flex items-center justify-between rounded-xl px-3 py-2 text-xs font-bold ring-1"
                                    :class="
                                        selectedOptions[opt.id]?.value === val.name
                                            ? 'bg-orange-500 text-white ring-orange-500'
                                            : 'bg-stone-50 text-stone-700 ring-stone-200 dark:bg-stone-800 dark:text-stone-200 dark:ring-stone-700'
                                    "
                                    @click="selectOptionValue(opt, val)"
                                >
                                    <span>{{ val.name }}</span>
                                    <span v-if="val.price > 0">+{{ val.price }}</span>
                                </button>
                            </div>
                        </div>

                        <div v-if="selectedItem.addons && selectedItem.addons.length > 0" class="mb-4 space-y-2">
                            <p class="text-xs font-bold">إضافات</p>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    v-for="addon in selectedItem.addons"
                                    :key="addon.id"
                                    type="button"
                                    class="flex items-center justify-between rounded-xl px-3 py-2 text-xs font-bold ring-1"
                                    :class="
                                        selectedAddons.some((a) => a.id === addon.id)
                                            ? 'bg-orange-50 text-orange-700 ring-orange-500 dark:bg-orange-950/30 dark:text-orange-200'
                                            : 'bg-stone-50 text-stone-700 ring-stone-200 dark:bg-stone-800 dark:text-stone-200 dark:ring-stone-700'
                                    "
                                    @click="toggleAddon(addon)"
                                >
                                    <span>{{ addon.name }}</span>
                                    <span>+{{ addon.price }}</span>
                                </button>
                            </div>
                        </div>

                        <label class="mb-3 block space-y-1.5">
                            <span class="flex items-center gap-1 text-xs font-bold">
                                <Sparkles class="h-3.5 w-3.5 text-orange-500" />
                                ملاحظة
                            </span>
                            <input
                                v-model="itemNotes"
                                type="text"
                                placeholder="طحينة زيادة، من غير شطة..."
                                class="w-full rounded-xl bg-stone-50 px-3 py-2.5 text-xs ring-1 ring-stone-200 focus:ring-2 focus:ring-orange-500 dark:bg-stone-800 dark:ring-stone-700"
                            />
                        </label>

                        <div class="mb-3 flex items-center justify-between">
                            <div class="flex items-center rounded-xl bg-stone-100 p-1 dark:bg-stone-800">
                                <button
                                    type="button"
                                    class="grid h-8 w-8 place-items-center"
                                    @click="modalQuantity = modalQuantity > 1 ? modalQuantity - 1 : 1"
                                >
                                    <Minus class="h-4 w-4" />
                                </button>
                                <span class="w-6 text-center font-black">{{ modalQuantity }}</span>
                                <button type="button" class="grid h-8 w-8 place-items-center" @click="modalQuantity++">
                                    <Plus class="h-4 w-4" />
                                </button>
                            </div>
                            <span class="text-lg font-black text-orange-600">
                                {{ (currentModalUnitPrice * modalQuantity).toFixed(0) }} ج.م
                            </span>
                        </div>

                        <button
                            v-if="canOrder"
                            type="button"
                            class="flex w-full items-center justify-center gap-2 rounded-2xl bg-orange-500 py-3.5 text-sm font-black text-white"
                            @click="handleAddToCartFromModal"
                        >
                            <ShoppingBag class="h-4 w-4" />
                            أضف للسلة
                        </button>
                        <button
                            v-else
                            type="button"
                            disabled
                            class="w-full rounded-2xl bg-stone-200 py-3.5 text-sm font-bold text-stone-400"
                        >
                            المطعم مغلق
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="showConflictModal" class="fixed inset-0 z-50 grid place-items-center bg-black/60 p-4">
                <div class="w-full max-w-sm rounded-3xl bg-white p-5 dark:bg-stone-900">
                    <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-amber-50 text-amber-500">
                        <AlertTriangle class="h-6 w-6" />
                    </div>
                    <h3 class="text-center text-base font-black">عندك طلب من مطعم تاني</h3>
                    <p class="mt-2 text-center text-xs text-stone-500">
                        السلة فيها طلب من {{ cart.restaurant?.name }}. نمسحها ونبدأ من {{ liveRestaurant.name }}؟
                    </p>
                    <div class="mt-4 flex gap-2">
                        <button
                            type="button"
                            class="flex-1 rounded-2xl bg-stone-100 py-3 text-xs font-bold dark:bg-stone-800"
                            @click="
                                showConflictModal = false;
                                pendingItemToAdd = null;
                            "
                        >
                            إلغاء
                        </button>
                        <button
                            type="button"
                            class="flex-[2] inline-flex items-center justify-center gap-1 rounded-2xl bg-orange-500 py-3 text-xs font-black text-white"
                            @click="confirmClearAndAdd"
                        >
                            <Check class="h-4 w-4" />
                            ابدأ طلب جديد
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
