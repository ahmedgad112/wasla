<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, CheckCircle, Clock } from '@lucide/vue';

interface Collection {
    id: number;
    amount: number;
    collected_at: string | null;
    notes: string | null;
    status: string;
    created_at: string;
    restaurant: { id: number; name: string };
    collectedBy: { name: string } | null;
    invoice: { invoice_number: string } | null;
}

interface Restaurant {
    id: number;
    name: string;
}

defineProps<{
    collections: { data: Collection[]; total: number; current_page: number; last_page: number };
    restaurants: Restaurant[];
    totalCollected: number;
    totalPending: number;
}>();

const showForm = ref(false);

const form = useForm({
    restaurant_id: '',
    amount: '',
    collected_at: new Date().toISOString().split('T')[0],
    notes: '',
});

const statusConfig: Record<string, { label: string; cls: string }> = {
    PENDING: { label: 'في الانتظار', cls: 'bg-amber-500/20 text-amber-400 border-amber-500/30' },
    COLLECTED: { label: 'تم التحصيل', cls: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' },
};

const fmt = (v: number): string => (v / 100).toFixed(2);

const statusOf = (status: string): { label: string; cls: string } =>
    statusConfig[status] ?? statusConfig.PENDING;

const handleSubmit = (): void => {
    form.post('/admin/collections', {
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
};

const formatDate = (date: string): string => new Date(date).toLocaleDateString('ar-EG');
</script>

<template>
    <Head title="التحصيلات" />

    <div class="space-y-6" dir="rtl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-stone-900">التحصيلات</h1>
                <p class="text-stone-400 text-sm mt-1">متابعة تحصيل عمولات المطاعم</p>
            </div>
            <button
                type="button"
                class="flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-400 text-white rounded-lg font-medium transition-colors"
                @click="showForm = !showForm"
            >
                <Plus class="w-4 h-4" />
                تسجيل تحصيل
            </button>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-5">
                <CheckCircle class="w-6 h-6 text-emerald-400 mb-2" />
                <p class="text-2xl font-bold text-stone-900">{{ fmt(totalCollected) }} ج</p>
                <p class="text-stone-400 text-sm mt-1">إجمالي المحصّل</p>
            </div>
            <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-5">
                <Clock class="w-6 h-6 text-amber-400 mb-2" />
                <p class="text-2xl font-bold text-stone-900">{{ fmt(totalPending) }} ج</p>
                <p class="text-stone-400 text-sm mt-1">في الانتظار</p>
            </div>
        </div>

        <div v-if="showForm" class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
            <h2 class="text-lg font-semibold text-stone-900 mb-4">تسجيل تحصيل جديد</h2>
            <form class="grid grid-cols-1 md:grid-cols-4 gap-4" @submit.prevent="handleSubmit">
                <div>
                    <label class="block text-sm text-stone-400 mb-1">المطعم *</label>
                    <select
                        v-model="form.restaurant_id"
                        class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                    >
                        <option value="">اختر المطعم</option>
                        <option v-for="r in restaurants" :key="r.id" :value="r.id">{{ r.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-stone-400 mb-1">المبلغ (ج.م) *</label>
                    <input
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        placeholder="0.00"
                        class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                    >
                </div>
                <div>
                    <label class="block text-sm text-stone-400 mb-1">تاريخ التحصيل</label>
                    <input
                        v-model="form.collected_at"
                        type="date"
                        class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                    >
                </div>
                <div>
                    <label class="block text-sm text-stone-400 mb-1">ملاحظات</label>
                    <input
                        v-model="form.notes"
                        type="text"
                        placeholder="تفاصيل..."
                        class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                    >
                </div>
                <div class="md:col-span-4 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        class="px-4 py-2 bg-white hover:bg-stone-100 text-stone-300 rounded-lg text-sm transition-colors"
                        @click="showForm = false"
                    >
                        إلغاء
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-orange-500 hover:bg-orange-400 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'جاري الحفظ...' : 'حفظ' }}
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white border border-stone-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="record-cards w-full text-sm">
                    <thead class="border-b border-stone-200">
                        <tr class="text-stone-400">
                            <th class="text-right px-6 py-4 font-medium">المطعم</th>
                            <th class="text-right px-6 py-4 font-medium">المبلغ</th>
                            <th class="text-right px-6 py-4 font-medium">الحالة</th>
                            <th class="text-right px-6 py-4 font-medium">تاريخ التحصيل</th>
                            <th class="text-right px-6 py-4 font-medium">الفاتورة</th>
                            <th class="text-right px-6 py-4 font-medium">بواسطة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr
                            v-for="col in collections.data"
                            :key="col.id"
                            class="hover:bg-white transition-colors"
                        >
                            <td data-label="المطعم" class="is-title px-6 py-4">
                                <Link
                                    :href="`/admin/restaurants/${col.restaurant.id}`"
                                    class="text-stone-900 hover:text-orange-400 transition-colors font-medium"
                                >
                                    {{ col.restaurant.name }}
                                </Link>
                            </td>
                            <td data-label="المبلغ" class="px-6 py-4 text-emerald-400 font-semibold">{{ fmt(col.amount) }} ج</td>
                            <td data-label="الحالة" class="px-6 py-4">
                                <span
                                    :class="[
                                        'px-2 py-1 rounded-full text-xs font-semibold border',
                                        statusOf(col.status).cls,
                                    ]"
                                >
                                    {{ statusOf(col.status).label }}
                                </span>
                            </td>
                            <td data-label="تاريخ التحصيل" class="px-6 py-4 text-stone-400">
                                {{ col.collected_at ? formatDate(col.collected_at) : '—' }}
                            </td>
                            <td data-label="الفاتورة" class="px-6 py-4">
                                <span v-if="col.invoice" class="text-indigo-400 text-xs">
                                    {{ col.invoice.invoice_number }}
                                </span>
                                <span v-else class="text-stone-500 text-xs">—</span>
                            </td>
                            <td data-label="بواسطة" class="px-6 py-4 text-stone-400 text-xs">{{ col.collectedBy?.name ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="collections.data.length === 0" class="text-stone-400 text-center py-12 text-sm">
                    لا توجد تحصيلات بعد
                </p>
            </div>
        </div>
    </div>
</template>
