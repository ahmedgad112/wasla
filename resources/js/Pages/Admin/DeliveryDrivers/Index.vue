<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { DeliveryDriver, Restaurant, PaginatedResponse } from '../../../Types';
import {
    Bike,
    Plus,
    Search,
    Filter,
    Trash2,
    Store,
    ToggleLeft,
    ToggleRight,
} from '@lucide/vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';

const props = defineProps<{
    drivers: PaginatedResponse<DeliveryDriver>;
    restaurants: Restaurant[];
    filters: { search?: string; restaurant_id?: string };
}>();

const items = computed(() => props.drivers?.data || []);
const search = ref(props.filters.search || '');
const restaurantId = ref(props.filters.restaurant_id || '');
const deleteId = ref<number | null>(null);

const pageNumbers = computed(() =>
    Array.from({ length: props.drivers.last_page }, (_, i) => i + 1),
);

const applyFilters = (): void => {
    router.get(
        '/admin/delivery-drivers',
        { search: search.value, restaurant_id: restaurantId.value },
        { preserveState: true },
    );
};

const onSearchKeydown = (e: KeyboardEvent): void => {
    if (e.key === 'Enter') {
        applyFilters();
    }
};

const confirmDelete = (): void => {
    if (deleteId.value !== null) {
        router.delete(`/admin/delivery-drivers/${deleteId.value}`);
        deleteId.value = null;
    }
};

const goToPage = (page: number): void => {
    router.get('/admin/delivery-drivers', { ...props.filters, page });
};
</script>

<template>
    <Head title="كباتن التوصيل — لوحة الإدارة" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-stone-900 dark:text-white">كباتن التوصيل</h1>
                <p class="text-xs text-stone-500 mt-0.5">
                    إدارة جميع مناديب التوصيل عبر المطاعم — {{ drivers.total }} مندوب مسجل
                </p>
            </div>
            <Link
                href="/admin/delivery-drivers/create"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs shadow transition"
            >
                <Plus class="w-4 h-4" />
                <span>إضافة مندوب جديد</span>
            </Link>
        </div>

        <div class="bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800 p-4 flex flex-wrap gap-3 shadow-xs">
            <div class="flex-1 min-w-48 relative">
                <Search class="absolute right-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-stone-400" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="ابحث بالاسم أو الهاتف..."
                    class="w-full pr-8 pl-3 py-2 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500"
                    @keydown="onSearchKeydown"
                >
            </div>
            <select
                v-model="restaurantId"
                class="px-3 py-2 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500"
            >
                <option value="">كل المطاعم</option>
                <option v-for="r in restaurants" :key="r.id" :value="r.id">{{ r.name }}</option>
            </select>
            <button
                type="button"
                class="px-4 py-2 rounded-xl bg-stone-800 dark:bg-stone-700 text-stone-900 text-xs font-bold flex items-center gap-1.5"
                @click="applyFilters"
            >
                <Filter class="w-3.5 h-3.5" />
                تصفية
            </button>
        </div>

        <div class="bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800 overflow-hidden shadow-xs">
            <div v-if="items.length === 0" class="text-center py-16">
                <Bike class="w-12 h-12 text-stone-300 dark:text-stone-600 mx-auto mb-3" />
                <p class="text-sm text-stone-500">لا يوجد مناديب توصيل مسجلون.</p>
            </div>
            <table v-else class="w-full text-xs">
                <thead class="bg-stone-50 dark:bg-stone-800/60 border-b border-stone-200 dark:border-stone-700">
                    <tr>
                        <th class="text-right px-5 py-3 font-bold text-stone-600 dark:text-stone-400">المندوب</th>
                        <th class="text-right px-5 py-3 font-bold text-stone-600 dark:text-stone-400">المطعم التابع له</th>
                        <th class="text-right px-5 py-3 font-bold text-stone-600 dark:text-stone-400">الهاتف</th>
                        <th class="text-right px-5 py-3 font-bold text-stone-600 dark:text-stone-400">الحالة</th>
                        <th class="px-5 py-3" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                    <tr
                        v-for="driver in items"
                        :key="driver.id"
                        class="hover:bg-stone-50 dark:hover:bg-stone-800/40 transition"
                    >
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-teal-600 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                                    {{ driver.name.charAt(0) }}
                                </div>
                                <div>
                                    <p class="font-bold text-stone-900 dark:text-white">{{ driver.name }}</p>
                                    <p class="text-stone-400 text-[10px]">{{ driver.user?.email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <span
                                v-if="driver.restaurant"
                                class="flex items-center gap-1.5 text-orange-600 dark:text-orange-400 font-bold"
                            >
                                <Store class="w-3.5 h-3.5" />
                                {{ driver.restaurant.name }}
                            </span>
                            <span
                                v-else
                                class="text-red-500 text-[10px] font-bold bg-red-50 dark:bg-red-950 px-2 py-0.5 rounded-full"
                            >
                                ⚠ غير مرتبط بمطعم
                            </span>
                        </td>
                        <td class="px-5 py-4 text-stone-600 dark:text-stone-300 font-mono">{{ driver.phone || '—' }}</td>
                        <td class="px-5 py-4">
                            <span
                                v-if="!driver.is_active"
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300"
                            >
                                معطل
                            </span>
                            <span
                                v-else-if="driver.availability_status === 'AVAILABLE'"
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                            >
                                متصل ومتاح
                            </span>
                            <span
                                v-else
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300"
                            >
                                مشغول / غير متاح
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    :title="driver.is_active ? 'تعطيل الحساب' : 'تفعيل الحساب'"
                                    class="p-1.5 rounded-lg text-stone-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/50 transition"
                                    @click="router.post(`/admin/delivery-drivers/${driver.id}/toggle`)"
                                >
                                    <ToggleRight v-if="driver.is_active" class="w-4 h-4" />
                                    <ToggleLeft v-else class="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    title="حذف المندوب"
                                    class="p-1.5 rounded-lg text-stone-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50 transition"
                                    @click="deleteId = driver.id"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="drivers.last_page > 1" class="flex items-center justify-center gap-2">
            <button
                v-for="page in pageNumbers"
                :key="page"
                type="button"
                :class="[
                    'w-8 h-8 rounded-lg text-xs font-bold transition',
                    page === drivers.current_page
                        ? 'bg-orange-600 text-white'
                        : 'bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300 hover:bg-stone-200',
                ]"
                @click="goToPage(page)"
            >
                {{ page }}
            </button>
        </div>
    </div>

    <ConfirmModal
        :is-open="deleteId !== null"
        message="هل أنت متأكد من حذف هذا المندوب؟ سيتم حذف حسابه وجميع بياناته نهائياً."
        confirm-text="حذف نهائياً"
        @confirm="confirmDelete"
        @cancel="deleteId = null"
    />
</template>
