<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import type { Order, PaginatedResponse } from '../../../Types';

const props = defineProps<{
    orders: PaginatedResponse<Order>;
    filters: { search?: string; status?: string };
}>();

const items = computed(() => props.orders?.data || []);
const search = ref(props.filters.search || '');

const statuses = ['ALL', 'PENDING', 'PREPARING', 'OUT_FOR_DELIVERY', 'DELIVERED', 'CANCELLED'] as const;

const statusLabel = (st: string): string => {
    if (st === 'ALL') return 'الكل';
    if (st === 'PENDING') return 'معلق';
    if (st === 'PREPARING') return 'تجهيز';
    if (st === 'OUT_FOR_DELIVERY') return 'مع الطيار';
    if (st === 'DELIVERED') return 'تم التسليم';
    return 'ملغي';
};

const handleSearch = (): void => {
    router.get('/admin/orders', { search: search.value, status: props.filters.status }, { preserveState: true });
};

const handleFilterStatus = (status: string): void => {
    router.get(
        '/admin/orders',
        { search: search.value, status: status === 'ALL' ? '' : status },
        { preserveState: true },
    );
};

const formatDate = (value: string): string =>
    new Date(value).toLocaleString('ar-EG', { dateStyle: 'short', timeStyle: 'short' });
</script>

<template>
    <Head title="جميع طلبات المنصة — الإدارة المركزية" />

    <div class="space-y-6">
        <div class="bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-stone-100 dark:border-stone-800">
                <div>
                    <h1 class="text-xl font-black text-stone-900 dark:text-white">
                        مراقبة وإدارة طلبات المنصة
                    </h1>
                    <p class="text-xs text-stone-400 mt-0.5">
                        سجل الطلبات المركزي عبر كافة مطاعم برج العرب
                    </p>
                </div>

                <div class="flex items-center gap-1.5 overflow-x-auto pb-2 sm:pb-0 custom-scrollbar">
                    <button
                        v-for="st in statuses"
                        :key="st"
                        type="button"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition',
                            filters.status === st || (!filters.status && st === 'ALL')
                                ? 'bg-orange-600 text-white shadow-xs'
                                : 'bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-400',
                        ]"
                        @click="handleFilterStatus(st)"
                    >
                        {{ statusLabel(st) }}
                    </button>
                </div>
            </div>

            <form class="mb-6 max-w-sm relative" @submit.prevent="handleSearch">
                <svg class="w-4 h-4 text-stone-400 absolute right-3.5 top-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input
                    v-model="search"
                    type="text"
                    placeholder="ابحث برقم الطلب (مثال: FS-2026...)"
                    class="w-full pr-10 pl-4 py-2 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white"
                />
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="border-b border-stone-200 dark:border-stone-800 text-stone-400 text-[11px] font-bold">
                            <th class="py-3 px-4">رقم الطلب</th>
                            <th class="py-3 px-4">المطعم</th>
                            <th class="py-3 px-4">العميل</th>
                            <th class="py-3 px-4">الكابتن</th>
                            <th class="py-3 px-4">الإجمالي</th>
                            <th class="py-3 px-4">عمولة المنصة</th>
                            <th class="py-3 px-4">الحالة</th>
                            <th class="py-3 px-4">التاريخ والوقت</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        <tr
                            v-for="order in items"
                            :key="order.id"
                            class="hover:bg-stone-50 dark:hover:bg-stone-800/50"
                        >
                            <td class="py-4 px-4 font-mono font-bold text-orange-600">
                                {{ order.order_number }}
                            </td>
                            <td class="py-4 px-4 font-bold text-stone-900 dark:text-white">
                                {{ order.restaurant?.name }}
                            </td>
                            <td class="py-4 px-4 text-stone-700 dark:text-stone-300">
                                {{ order.customer?.user?.name || 'عميل' }}
                            </td>
                            <td class="py-4 px-4 text-stone-600 dark:text-stone-400">
                                {{ order.deliveryDriver?.name || '—' }}
                            </td>
                            <td class="py-4 px-4 font-black text-stone-900 dark:text-white">
                                {{ order.total_amount }} ج.م
                            </td>
                            <td class="py-4 px-4 font-bold text-emerald-600 dark:text-emerald-400">
                                {{ order.platform_commission_amount || 0 }} ج.م
                            </td>
                            <td class="py-4 px-4">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300">
                                    {{ order.status }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-stone-400 font-mono text-[11px]">
                                {{ formatDate(order.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
