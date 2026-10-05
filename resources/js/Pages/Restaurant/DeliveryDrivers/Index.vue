<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import type { DeliveryDriver, PaginatedResponse, Restaurant } from '../../../Types';
import { Bike, Edit, Phone, Plus, Search, Trash2, UserRound } from '@lucide/vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';

const props = withDefaults(
    defineProps<{
        drivers: PaginatedResponse<DeliveryDriver>;
        restaurant: Restaurant;
        filters?: { search?: string; status?: string };
        stats?: {
            total: number;
            available: number;
            busy: number;
            inactive: number;
        };
    }>(),
    {
        filters: () => ({}),
        stats: () => ({ total: 0, available: 0, busy: 0, inactive: 0 }),
    },
);

const items = computed(() => props.drivers?.data || []);
const search = ref(props.filters.search || '');
const confirmDelete = ref<number | null>(null);

const vehicleLabels: Record<string, string> = {
    MOTORCYCLE: 'دراجة نارية',
    SCOOTER: 'سكوتر',
    CAR: 'سيارة',
    BICYCLE: 'دراجة',
    WALKING: 'مشياً',
};

const statusFilters = [
    { key: '', label: 'الكل' },
    { key: 'available', label: 'متاح' },
    { key: 'busy', label: 'في توصيل' },
    { key: 'offline', label: 'غير متصل' },
    { key: 'inactive', label: 'حساب معطل' },
];

const applyFilters = (status = props.filters.status || ''): void => {
    router.get(
        '/restaurant/delivery-drivers',
        {
            search: search.value || undefined,
            status: status || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const availabilityLabel = (driver: DeliveryDriver): string => {
    if (!driver.is_active) {
        return 'حساب معطل';
    }
    if (driver.availability_status === 'AVAILABLE') {
        return 'متاح الآن';
    }
    if (driver.availability_status === 'BUSY') {
        return 'في توصيل';
    }
    return 'غير متصل';
};

const availabilityClass = (driver: DeliveryDriver): string => {
    if (!driver.is_active) {
        return 'bg-red-50 text-red-700 border-red-200';
    }
    if (driver.availability_status === 'AVAILABLE') {
        return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    }
    if (driver.availability_status === 'BUSY') {
        return 'bg-amber-50 text-amber-700 border-amber-200';
    }
    return 'bg-stone-100 text-stone-600 border-stone-200';
};

const paginationClass = (link: { url: string | null; active: boolean }): string => {
    if (link.active) {
        return 'bg-orange-600 text-white';
    }
    if (!link.url) {
        return 'text-stone-300 cursor-not-allowed';
    }
    return 'text-stone-600 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800 bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800';
};

const toggleDriver = (id: number): void => {
    router.post(`/restaurant/delivery-drivers/${id}/toggle`, {}, { preserveScroll: true });
};

const confirmDeleteAction = (): void => {
    if (confirmDelete.value === null) {
        return;
    }
    router.delete(`/restaurant/delivery-drivers/${confirmDelete.value}`, {
        preserveScroll: true,
        onFinish: () => {
            confirmDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="كباتن التوصيل — بوابة المطعم" />

    <div class="space-y-6 pb-12" dir="rtl">
        <div class="relative overflow-hidden p-6 sm:p-7 rounded-3xl bg-gradient-to-r from-orange-600 via-amber-600 to-orange-500 text-white shadow-xl">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 text-xs font-bold mb-2">
                        <Bike class="w-3.5 h-3.5" />
                        <span>فريق توصيل {{ restaurant.name }}</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black">كباتن التوصيل</h1>
                    <p class="text-orange-100 text-xs sm:text-sm mt-1 max-w-xl">
                        أضف كباتن المطعم، فعّل حساباتهم، وتابع مين متاح ومين في توصيل.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        href="/restaurant/driver-stats"
                        class="px-4 py-2.5 rounded-2xl bg-white/15 hover:bg-white/25 text-white font-bold text-xs"
                    >
                        إحصائيات الكباتن
                    </Link>
                    <Link
                        href="/restaurant/delivery-drivers/create"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl bg-white text-orange-600 font-black text-xs shadow-md"
                    >
                        <Plus class="w-4 h-4" />
                        إضافة كابتن
                    </Link>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <button type="button" class="p-4 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 text-right shadow-xs" @click="applyFilters('')">
                <p class="text-[11px] font-bold text-stone-400">كل الكباتن</p>
                <p class="mt-1 text-2xl font-black text-stone-900 dark:text-white">{{ stats.total }}</p>
            </button>
            <button type="button" class="p-4 rounded-3xl bg-white dark:bg-stone-900 border border-emerald-200 dark:border-emerald-900 text-right shadow-xs" @click="applyFilters('available')">
                <p class="text-[11px] font-bold text-emerald-600">متاحون الآن</p>
                <p class="mt-1 text-2xl font-black text-emerald-600">{{ stats.available }}</p>
            </button>
            <button type="button" class="p-4 rounded-3xl bg-white dark:bg-stone-900 border border-amber-200 dark:border-amber-900 text-right shadow-xs" @click="applyFilters('busy')">
                <p class="text-[11px] font-bold text-amber-600">في توصيل</p>
                <p class="mt-1 text-2xl font-black text-amber-600">{{ stats.busy }}</p>
            </button>
            <button type="button" class="p-4 rounded-3xl bg-white dark:bg-stone-900 border border-red-200 dark:border-red-900 text-right shadow-xs" @click="applyFilters('inactive')">
                <p class="text-[11px] font-bold text-red-600">حسابات معطلة</p>
                <p class="mt-1 text-2xl font-black text-red-600">{{ stats.inactive }}</p>
            </button>
        </div>

        <div class="bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-4 shadow-xs space-y-3">
            <form class="flex flex-col sm:flex-row gap-3" @submit.prevent="applyFilters()">
                <div class="relative flex-1">
                    <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="ابحث بالاسم أو رقم الهاتف..."
                        class="w-full pr-10 pl-3 py-2.5 text-xs rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white"
                    >
                </div>
                <button type="submit" class="px-5 py-2.5 rounded-2xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-black">
                    بحث
                </button>
            </form>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="filter in statusFilters"
                    :key="filter.key"
                    type="button"
                    :class="[
                        'px-3 py-1.5 rounded-full text-[11px] font-black border transition',
                        (filters.status || '') === filter.key
                            ? 'bg-orange-600 text-white border-orange-600'
                            : 'bg-stone-50 dark:bg-stone-800 text-stone-600 dark:text-stone-300 border-stone-200 dark:border-stone-700',
                    ]"
                    @click="applyFilters(filter.key)"
                >
                    {{ filter.label }}
                </button>
            </div>
        </div>

        <div v-if="items.length === 0" class="rounded-3xl border border-dashed border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-900 px-6 py-16 text-center">
            <Bike class="w-12 h-12 text-stone-300 mx-auto mb-3" />
            <p class="text-sm font-black text-stone-700 dark:text-stone-200">لسه مفيش كباتن في المطعم</p>
            <p class="text-xs text-stone-400 mt-1">أضف كابتن عشان تقدر تعيّنه على الطلبات الجاهزة.</p>
            <Link
                href="/restaurant/delivery-drivers/create"
                class="inline-flex items-center gap-1.5 mt-4 px-4 py-2.5 rounded-2xl bg-orange-600 text-white text-xs font-black"
            >
                <Plus class="w-4 h-4" />
                إضافة أول كابتن
            </Link>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <article
                v-for="driver in items"
                :key="driver.id"
                class="rounded-3xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-5 shadow-xs space-y-4"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-11 h-11 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center font-black shrink-0">
                            {{ driver.name.charAt(0) }}
                        </div>
                        <div class="min-w-0">
                            <h2 class="font-black text-stone-900 dark:text-white truncate">{{ driver.name }}</h2>
                            <p class="text-[11px] text-stone-400 truncate">{{ driver.user?.email || 'بدون بريد' }}</p>
                        </div>
                    </div>
                    <span :class="['shrink-0 px-2.5 py-1 rounded-full text-[10px] font-black border', availabilityClass(driver)]">
                        {{ availabilityLabel(driver) }}
                    </span>
                </div>

                <div class="space-y-2 text-xs text-stone-600 dark:text-stone-300">
                    <p class="flex items-center gap-2 font-mono">
                        <Phone class="w-3.5 h-3.5 text-stone-400" />
                        {{ driver.phone || '—' }}
                    </p>
                    <p class="flex items-center gap-2">
                        <Bike class="w-3.5 h-3.5 text-stone-400" />
                        {{ vehicleLabels[driver.vehicle_type || 'MOTORCYCLE'] || driver.vehicle_type }}
                        <span v-if="driver.vehicle_plate" class="font-mono text-stone-400">· {{ driver.vehicle_plate }}</span>
                    </p>
                    <p class="flex items-center gap-2">
                        <UserRound class="w-3.5 h-3.5 text-stone-400" />
                        {{ driver.active_orders_count || 0 }} طلب نشط
                    </p>
                </div>

                <div class="flex items-center gap-2 pt-3 border-t border-stone-100 dark:border-stone-800">
                    <button
                        type="button"
                        class="flex-1 py-2 rounded-xl text-[11px] font-black border border-stone-200 dark:border-stone-700 text-stone-700 dark:text-stone-200 hover:bg-stone-50 dark:hover:bg-stone-800"
                        @click="toggleDriver(driver.id)"
                    >
                        {{ driver.is_active ? 'تعطيل الحساب' : 'تفعيل الحساب' }}
                    </button>
                    <Link
                        :href="`/restaurant/delivery-drivers/${driver.id}/edit`"
                        class="p-2 rounded-xl text-stone-400 hover:text-orange-600 hover:bg-orange-50"
                        title="تعديل"
                    >
                        <Edit class="w-4 h-4" />
                    </Link>
                    <button
                        type="button"
                        class="p-2 rounded-xl text-stone-400 hover:text-red-600 hover:bg-red-50"
                        title="حذف"
                        @click="confirmDelete = driver.id"
                    >
                        <Trash2 class="w-4 h-4" />
                    </button>
                </div>
            </article>
        </div>

        <div v-if="drivers.links && drivers.links.length > 3" class="flex items-center justify-center gap-1.5">
            <Link
                v-for="(link, index) in drivers.links"
                :key="index"
                :href="link.url || '#'"
                preserve-scroll
                :class="['px-3.5 py-2 rounded-xl text-xs font-bold transition', paginationClass(link)]"
                v-html="link.label"
            />
        </div>
    </div>

    <ConfirmModal
        :is-open="confirmDelete !== null"
        message="هل أنت متأكد من حذف هذا الكابتن؟ لن تتمكن من استعادته."
        confirm-text="حذف"
        @confirm="confirmDeleteAction"
        @cancel="confirmDelete = null"
    />
</template>
