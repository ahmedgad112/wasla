<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import type { DeliveryDriver, PaginatedResponse, Restaurant } from '../../../Types';
import {
    Bike,
    Phone,
    Plus,
    Search,
    Store,
    Trash2,
    UserRound,
} from '@lucide/vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';

const props = withDefaults(
    defineProps<{
        drivers: PaginatedResponse<DeliveryDriver>;
        restaurants: Restaurant[];
        filters: { search?: string; restaurant_id?: string; status?: string };
        stats?: {
            total: number;
            available: number;
            busy: number;
            inactive: number;
        };
    }>(),
    {
        stats: () => ({ total: 0, available: 0, busy: 0, inactive: 0 }),
    },
);

const items = computed(() => props.drivers?.data || []);
const search = ref(props.filters.search || '');
const restaurantId = ref(props.filters.restaurant_id || '');
const deleteId = ref<number | null>(null);

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
        '/admin/delivery-drivers',
        {
            search: search.value || undefined,
            restaurant_id: restaurantId.value || undefined,
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
        return 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/50 dark:text-red-300 dark:border-red-900';
    }
    if (driver.availability_status === 'AVAILABLE') {
        return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-900';
    }
    if (driver.availability_status === 'BUSY') {
        return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-900';
    }
    return 'bg-stone-100 text-stone-600 border-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:border-stone-700';
};

const paginationClass = (link: { url: string | null; active: boolean }): string => {
    if (link.active) {
        return 'bg-orange-600 text-white shadow-xs';
    }
    if (!link.url) {
        return 'text-stone-300 dark:text-stone-600 cursor-not-allowed';
    }
    return 'text-stone-600 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800 bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800';
};

const confirmDelete = (): void => {
    if (deleteId.value === null) {
        return;
    }
    router.delete(`/admin/delivery-drivers/${deleteId.value}`, {
        preserveScroll: true,
        onFinish: () => {
            deleteId.value = null;
        },
    });
};

const toggleDriver = (id: number): void => {
    router.post(`/admin/delivery-drivers/${id}/toggle`, {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="كباتن التوصيل — لوحة الإدارة" />

    <div class="space-y-6 pb-12" dir="rtl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="w-2 h-2 rounded-full bg-orange-500" />
                    <span class="text-xs font-bold text-orange-600 dark:text-orange-400">فريق التوصيل</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-stone-900 dark:text-white">كباتن التوصيل</h1>
                <p class="text-stone-500 dark:text-stone-400 text-xs sm:text-sm mt-0.5">
                    حسابات الكباتن، المطعم التابع لكل واحد، وحالة الاتصال على الطريق
                </p>
            </div>
            <Link
                v-if="$can('drivers.manage')"
                href="/admin/delivery-drivers/create"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-700 hover:to-amber-600 text-white rounded-2xl font-black text-xs shadow-md shadow-orange-600/20 transition self-start"
            >
                <Plus class="w-4 h-4" />
                <span>إضافة كابتن</span>
            </Link>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <button
                type="button"
                class="p-4 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 text-right shadow-xs"
                @click="applyFilters('')"
            >
                <p class="text-[11px] font-bold text-stone-400">كل الكباتن</p>
                <p class="mt-1 text-2xl font-black text-stone-900 dark:text-white">{{ stats.total }}</p>
            </button>
            <button
                type="button"
                class="p-4 rounded-3xl bg-white dark:bg-stone-900 border border-emerald-200 dark:border-emerald-900 text-right shadow-xs"
                @click="applyFilters('available')"
            >
                <p class="text-[11px] font-bold text-emerald-600">متاحون الآن</p>
                <p class="mt-1 text-2xl font-black text-emerald-600">{{ stats.available }}</p>
            </button>
            <button
                type="button"
                class="p-4 rounded-3xl bg-white dark:bg-stone-900 border border-amber-200 dark:border-amber-900 text-right shadow-xs"
                @click="applyFilters('busy')"
            >
                <p class="text-[11px] font-bold text-amber-600">في توصيل</p>
                <p class="mt-1 text-2xl font-black text-amber-600">{{ stats.busy }}</p>
            </button>
            <button
                type="button"
                class="p-4 rounded-3xl bg-white dark:bg-stone-900 border border-red-200 dark:border-red-900 text-right shadow-xs"
                @click="applyFilters('inactive')"
            >
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
                        placeholder="ابحث بالاسم أو الهاتف أو البريد..."
                        class="w-full pr-10 pl-3 py-2.5 text-xs rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-orange-500"
                    >
                </div>
                <select
                    v-model="restaurantId"
                    class="px-3 py-2.5 text-xs font-bold rounded-2xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-orange-500"
                    @change="applyFilters()"
                >
                    <option value="">كل الجهات</option>
                    <option value="platform">كباتن الموقع</option>
                    <option v-for="restaurant in restaurants" :key="restaurant.id" :value="restaurant.id">
                        {{ restaurant.name }}
                    </option>
                </select>
                <button
                    type="submit"
                    class="px-5 py-2.5 rounded-2xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-black"
                >
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
            <Bike class="w-12 h-12 text-stone-300 dark:text-stone-600 mx-auto mb-3" />
            <p class="text-sm font-black text-stone-700 dark:text-stone-200">مفيش كباتن مطابقين للبحث</p>
            <p class="text-xs text-stone-400 mt-1">غيّر التصفية، أو أضف كابتن جديد واربطه بمطعم.</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <article
                v-for="driver in items"
                :key="driver.id"
                class="rounded-3xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-900 p-5 shadow-xs space-y-4"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-11 h-11 rounded-2xl bg-orange-100 dark:bg-stone-800 text-orange-600 dark:text-orange-400 flex items-center justify-center font-black shrink-0">
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

                <div class="grid grid-cols-1 gap-2 text-xs text-stone-600 dark:text-stone-300">
                    <p class="flex items-center gap-2">
                        <Store class="w-3.5 h-3.5 text-orange-500 shrink-0" />
                        <span v-if="driver.restaurant" class="font-bold text-orange-600 dark:text-orange-400">{{ driver.restaurant.name }}</span>
                        <span v-else class="font-bold text-sky-600 dark:text-sky-400">من الموقع</span>
                    </p>
                    <p class="flex items-center gap-2 font-mono">
                        <Phone class="w-3.5 h-3.5 text-stone-400 shrink-0" />
                        {{ driver.phone || '—' }}
                    </p>
                    <p class="flex items-center gap-2">
                        <Bike class="w-3.5 h-3.5 text-stone-400 shrink-0" />
                        {{ vehicleLabels[driver.vehicle_type || 'MOTORCYCLE'] || driver.vehicle_type }}
                        <span v-if="driver.vehicle_plate" class="font-mono text-stone-400">· {{ driver.vehicle_plate }}</span>
                    </p>
                    <p class="flex items-center gap-2">
                        <UserRound class="w-3.5 h-3.5 text-stone-400 shrink-0" />
                        {{ driver.active_orders_count || 0 }} طلب نشط
                    </p>
                </div>

                <div v-if="$can('drivers.manage')" class="flex items-center gap-2 pt-3 border-t border-stone-100 dark:border-stone-800">
                    <button
                        type="button"
                        class="flex-1 py-2 rounded-xl text-[11px] font-black border border-stone-200 dark:border-stone-700 hover:bg-stone-50 dark:hover:bg-stone-800"
                        @click="toggleDriver(driver.id)"
                    >
                        {{ driver.is_active ? 'تعطيل الحساب' : 'تفعيل الحساب' }}
                    </button>
                    <button
                        type="button"
                        class="p-2 rounded-xl text-stone-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40"
                        title="حذف الكابتن"
                        @click="deleteId = driver.id"
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
        :is-open="deleteId !== null"
        message="هل أنت متأكد من حذف هذا الكابتن؟ سيتم حذف حسابه وجميع بياناته نهائياً."
        confirm-text="حذف نهائياً"
        @confirm="confirmDelete"
        @cancel="deleteId = null"
    />
</template>
