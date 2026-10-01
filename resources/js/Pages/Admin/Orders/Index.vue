<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Archive, ArchiveRestore, Trash2 } from '@lucide/vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import type { Order, PaginatedResponse } from '../../../Types';

const props = defineProps<{
    orders: PaginatedResponse<Order>;
    filters: { search?: string; status?: string; archive?: string };
}>();

const items = computed(() => props.orders?.data || []);
const search = ref(props.filters.search || '');
const selectedIds = ref<number[]>([]);
const pendingDeleteIds = ref<number[]>([]);

const statuses = ['ALL', 'PENDING', 'PREPARING', 'OUT_FOR_DELIVERY', 'DELIVERED', 'CANCELLED'] as const;
const viewingArchive = computed(() => props.filters.archive === 'archived');

const statusLabel = (st: string): string => {
    if (st === 'ALL') return 'الكل';
    if (st === 'PENDING') return 'معلق';
    if (st === 'PREPARING') return 'تجهيز';
    if (st === 'OUT_FOR_DELIVERY') return 'مع الطيار';
    if (st === 'DELIVERED') return 'تم التسليم';
    return 'ملغي';
};

const listParams = (overrides: { status?: string; archive?: string } = {}): Record<string, string> => {
    const params: Record<string, string> = {};
    const status = overrides.status ?? props.filters.status;
    const archive = overrides.archive ?? (viewingArchive.value ? 'archived' : '');

    if (search.value) {
        params.search = search.value;
    }
    if (status) {
        params.status = status;
    }
    if (archive === 'archived') {
        params.archive = 'archived';
    }

    return params;
};

const handleSearch = (): void => {
    router.get('/admin/orders', listParams(), { preserveState: true, preserveScroll: true });
};

const handleFilterStatus = (status: string): void => {
    router.get(
        '/admin/orders',
        listParams({ status: status === 'ALL' ? '' : status }),
        { preserveState: true, preserveScroll: true },
    );
};

const setArchiveView = (archive: '' | 'archived'): void => {
    selectedIds.value = [];
    router.get('/admin/orders', listParams({ archive }), { preserveState: true, preserveScroll: true });
};

const allSelected = computed(() => items.value.length > 0 && items.value.every((order) => selectedIds.value.includes(order.id)));

const toggleAll = (): void => {
    selectedIds.value = allSelected.value ? [] : items.value.map((order) => order.id);
};

const toggleOne = (id: number): void => {
    selectedIds.value = selectedIds.value.includes(id)
        ? selectedIds.value.filter((item) => item !== id)
        : [...selectedIds.value, id];
};

const finishSelection = (): void => {
    selectedIds.value = [];
};

const archiveOrders = (ids: number[]): void => {
    if (ids.length === 1) {
        router.post(`/admin/orders/${ids[0]}/archive`, {}, { preserveScroll: true, onFinish: finishSelection });
        return;
    }

    router.post('/admin/orders/archive', { ids }, { preserveScroll: true, onFinish: finishSelection });
};

const restoreOrders = (ids: number[]): void => {
    if (ids.length === 1) {
        router.post(`/admin/orders/${ids[0]}/restore`, {}, { preserveScroll: true, onFinish: finishSelection });
        return;
    }

    router.post('/admin/orders/restore', { ids }, { preserveScroll: true, onFinish: finishSelection });
};

const askDelete = (ids: number[]): void => {
    pendingDeleteIds.value = ids;
};

const confirmDelete = (): void => {
    const ids = pendingDeleteIds.value;

    if (ids.length === 1) {
        router.delete(`/admin/orders/${ids[0]}`, {
            preserveScroll: true,
            onFinish: () => {
                pendingDeleteIds.value = [];
                finishSelection();
            },
        });
        return;
    }

    router.delete('/admin/orders/bulk', {
        data: { ids },
        preserveScroll: true,
        onFinish: () => {
            pendingDeleteIds.value = [];
            finishSelection();
        },
    });
};

const deleteMessage = computed(() => (
    pendingDeleteIds.value.length > 1
        ? `هل تريد حذف ${pendingDeleteIds.value.length} طلبات؟ لن تظهر بعد ذلك في قوائم الطلبات.`
        : 'هل تريد حذف هذا الطلب؟ لن يظهر بعد ذلك في قوائم الطلبات.'
));

const formatDate = (value: string): string =>
    new Date(value).toLocaleString('ar-EG', { dateStyle: 'short', timeStyle: 'short' });

const paginationClass = (link: { url: string | null; active: boolean }): string => {
    if (link.active) {
        return 'bg-orange-600 text-white shadow-xs';
    }
    if (!link.url) {
        return 'text-stone-300 dark:text-stone-600 cursor-not-allowed';
    }
    return 'text-stone-600 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-stone-800 bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800';
};

watch(items, () => {
    selectedIds.value = [];
});
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
                        {{ viewingArchive ? 'الطلبات المؤرشفة. يمكن استعادتها أو حذفها.' : 'سجل الطلبات المركزي عبر كافة مطاعم برج العرب' }}
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

            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <form class="max-w-sm relative w-full" @submit.prevent="handleSearch">
                    <svg class="w-4 h-4 text-stone-400 absolute right-3.5 top-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="ابحث برقم الطلب (مثال: FS-2026...)"
                        class="w-full pr-10 pl-4 py-2 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white"
                    />
                </form>

                <div class="flex items-center gap-1.5">
                    <button
                        type="button"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold transition',
                            viewingArchive
                                ? 'bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-400'
                                : 'bg-stone-900 text-white dark:bg-white dark:text-stone-900',
                        ]"
                        @click="setArchiveView('')"
                    >
                        النشطة
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold transition',
                            viewingArchive
                                ? 'bg-stone-900 text-white dark:bg-white dark:text-stone-900'
                                : 'bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-400',
                        ]"
                        @click="setArchiveView('archived')"
                    >
                        الأرشيف
                    </button>
                </div>
            </div>

            <div
                v-if="selectedIds.length > 0 && $can('orders.manage')"
                class="mb-4 flex flex-wrap items-center gap-2 rounded-2xl bg-orange-50 dark:bg-orange-950/30 border border-orange-100 dark:border-orange-900 px-4 py-3"
            >
                <span class="text-xs font-bold text-orange-700 dark:text-orange-300">
                    {{ selectedIds.length }} محدد
                </span>
                <button
                    v-if="!viewingArchive"
                    type="button"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold bg-white dark:bg-stone-900 text-stone-700 dark:text-stone-200 border border-stone-200 dark:border-stone-700"
                    @click="archiveOrders(selectedIds)"
                >
                    أرشفة المحدد
                </button>
                <button
                    v-else
                    type="button"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold bg-white dark:bg-stone-900 text-stone-700 dark:text-stone-200 border border-stone-200 dark:border-stone-700"
                    @click="restoreOrders(selectedIds)"
                >
                    استعادة المحدد
                </button>
                <button
                    type="button"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold bg-red-600 text-white"
                    @click="askDelete(selectedIds)"
                >
                    حذف المحدد
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="border-b border-stone-200 dark:border-stone-800 text-stone-400 text-[11px] font-bold">
                            <th v-if="$can('orders.manage')" class="py-3 px-4 w-10">
                                <input
                                    type="checkbox"
                                    class="rounded border-stone-300"
                                    :checked="allSelected"
                                    @change="toggleAll"
                                />
                            </th>
                            <th class="py-3 px-4">رقم الطلب</th>
                            <th class="py-3 px-4">المطعم</th>
                            <th class="py-3 px-4">العميل</th>
                            <th class="py-3 px-4">الكابتن</th>
                            <th class="py-3 px-4">الإجمالي</th>
                            <th class="py-3 px-4">عمولة المنصة</th>
                            <th class="py-3 px-4">الحالة</th>
                            <th class="py-3 px-4">التاريخ والوقت</th>
                            <th v-if="$can('orders.manage')" class="py-3 px-4">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        <tr
                            v-for="order in items"
                            :key="order.id"
                            class="hover:bg-stone-50 dark:hover:bg-stone-800/50"
                        >
                            <td v-if="$can('orders.manage')" class="py-4 px-4">
                                <input
                                    type="checkbox"
                                    class="rounded border-stone-300"
                                    :checked="selectedIds.includes(order.id)"
                                    @change="toggleOne(order.id)"
                                />
                            </td>
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
                            <td v-if="$can('orders.manage')" class="py-4 px-4">
                                <div class="flex items-center gap-1">
                                    <button
                                        v-if="!viewingArchive"
                                        type="button"
                                        class="p-2 rounded-xl text-stone-500 hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-stone-800 transition"
                                        title="أرشفة"
                                        @click="archiveOrders([order.id])"
                                    >
                                        <Archive class="w-4 h-4" />
                                    </button>
                                    <button
                                        v-else
                                        type="button"
                                        class="p-2 rounded-xl text-stone-500 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-stone-800 transition"
                                        title="استعادة"
                                        @click="restoreOrders([order.id])"
                                    >
                                        <ArchiveRestore class="w-4 h-4" />
                                    </button>
                                    <button
                                        type="button"
                                        class="p-2 rounded-xl text-stone-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-stone-800 transition"
                                        title="حذف"
                                        @click="askDelete([order.id])"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="items.length === 0" class="text-center py-16">
                    <p class="text-xs text-stone-400">
                        {{ viewingArchive ? 'لا توجد طلبات في الأرشيف.' : 'لا توجد طلبات مطابقة.' }}
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="orders?.links && orders.links.length > 3"
            class="flex items-center justify-center gap-1.5"
        >
            <Link
                v-for="(link, idx) in orders.links"
                :key="idx"
                :href="link.url || '#'"
                preserve-scroll
                :class="['px-3.5 py-2 rounded-xl text-xs font-bold transition', paginationClass(link)]"
                v-html="link.label"
            />
        </div>
    </div>

    <ConfirmModal
        :is-open="pendingDeleteIds.length > 0"
        :message="deleteMessage"
        confirm-text="حذف"
        cancel-text="إلغاء"
        @confirm="confirmDelete"
        @cancel="pendingDeleteIds = []"
    />
</template>
