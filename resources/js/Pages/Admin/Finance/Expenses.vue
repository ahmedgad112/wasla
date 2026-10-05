<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import { ArrowLeft, Plus, Trash2 } from '@lucide/vue';

interface Expense {
    id: number;
    description: string;
    amount: number;
    category: string;
    date: string;
    notes: string | null;
}

interface ExpenseCategory {
    name: string;
    total: number;
    count: number;
}

const props = defineProps<{
    expenses: { data: Expense[]; total: number; current_page: number; last_page: number };
    categories: ExpenseCategory[];
    totalThisMonth: number;
    totalThisYear: number;
}>();

const showForm = ref(false);
const confirmDeleteId = ref<number | null>(null);

const form = useForm({
    description: '',
    amount: '',
    category: '',
    date: new Date().toISOString().split('T')[0],
    notes: '',
});

const fmt = (v: number): string => (v / 100).toFixed(2);

const summaryCards = computed(() => [
    { label: 'هذا الشهر', value: `${fmt(props.totalThisMonth)} ج`, color: 'text-red-400' },
    { label: 'هذا العام', value: `${fmt(props.totalThisYear)} ج`, color: 'text-orange-400' },
    ...props.categories.slice(0, 2).map((c) => ({
        label: c.name,
        value: `${fmt(c.total)} ج`,
        color: 'text-stone-300',
    })),
]);

const handleSubmit = (): void => {
    form.post('/admin/finance/expenses', {
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
};

const confirmDelete = (): void => {
    if (confirmDeleteId.value !== null) {
        router.delete(`/admin/finance/expenses/${confirmDeleteId.value}`);
        confirmDeleteId.value = null;
    }
};

const formatDate = (value: string): string => new Date(value).toLocaleDateString('ar-EG');
</script>

<template>
    <Head title="المصروفات" />

    <div class="space-y-6" dir="rtl">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link
                    href="/admin/finance"
                    class="p-2 rounded-lg bg-white hover:bg-stone-100 text-stone-400 hover:text-stone-900 transition-colors"
                >
                    <ArrowLeft class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-stone-900">المصروفات</h1>
                    <p class="text-stone-400 text-sm mt-1">إدارة مصروفات المنصة</p>
                </div>
            </div>
            <button
                v-if="$can('finance.manage')"
                type="button"
                class="flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-400 text-white rounded-lg font-medium transition-colors"
                @click="showForm = !showForm"
            >
                <Plus class="w-4 h-4" />
                إضافة مصروف
            </button>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
                v-for="(card, i) in summaryCards"
                :key="i"
                class="bg-white border border-stone-200 rounded-xl p-4"
            >
                <p :class="['text-xl font-bold', card.color]">{{ card.value }}</p>
                <p class="text-stone-400 text-xs mt-1">{{ card.label }}</p>
            </div>
        </div>

        <div v-if="showForm" class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
            <h2 class="text-lg font-semibold text-stone-900 mb-4">إضافة مصروف جديد</h2>
            <form class="grid grid-cols-1 md:grid-cols-3 gap-4" @submit.prevent="handleSubmit">
                <div class="md:col-span-2">
                    <label class="block text-sm text-stone-400 mb-1">الوصف *</label>
                    <input
                        v-model="form.description"
                        type="text"
                        placeholder="وصف المصروف"
                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors"
                    />
                    <p v-if="form.errors.description" class="text-red-400 text-xs mt-1">
                        {{ form.errors.description }}
                    </p>
                </div>
                <div>
                    <label class="block text-sm text-stone-400 mb-1">المبلغ (ج.م) *</label>
                    <input
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        placeholder="0.00"
                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors"
                    />
                </div>
                <div>
                    <label class="block text-sm text-stone-400 mb-1">الفئة</label>
                    <input
                        v-model="form.category"
                        type="text"
                        placeholder="تشغيل، تسويق..."
                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors"
                    />
                </div>
                <div>
                    <label class="block text-sm text-stone-400 mb-1">التاريخ</label>
                    <input
                        v-model="form.date"
                        type="date"
                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                    />
                </div>
                <div>
                    <label class="block text-sm text-stone-400 mb-1">ملاحظات</label>
                    <input
                        v-model="form.notes"
                        type="text"
                        placeholder="ملاحظات إضافية"
                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors"
                    />
                </div>
                <div class="md:col-span-3 flex items-center justify-end gap-3 pt-2">
                    <button
                        type="button"
                        class="px-4 py-2 bg-white hover:bg-stone-100 text-stone-600 rounded-lg font-medium border border-stone-200 transition-colors"
                        @click="showForm = false"
                    >
                        إلغاء
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-orange-500 hover:bg-orange-400 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                    >
                        {{ form.processing ? 'جاري الحفظ...' : 'حفظ' }}
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
            <h2 class="text-lg font-semibold text-stone-900 mb-4">سجل المصروفات</h2>
            <div class="overflow-x-auto">
                <table class="record-cards w-full text-sm">
                    <thead>
                        <tr class="text-stone-400 border-b border-stone-200">
                            <th class="text-right pb-3 font-medium">الوصف</th>
                            <th class="text-right pb-3 font-medium">الفئة</th>
                            <th class="text-right pb-3 font-medium">المبلغ</th>
                            <th class="text-right pb-3 font-medium">التاريخ</th>
                            <th class="text-right pb-3 font-medium" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr
                            v-for="exp in expenses.data"
                            :key="exp.id"
                            class="hover:bg-white transition-colors"
                        >
                            <td data-label="الوصف" class="is-title py-3">
                                <p class="text-stone-900">{{ exp.description }}</p>
                                <p v-if="exp.notes" class="text-stone-500 text-xs">{{ exp.notes }}</p>
                            </td>
                            <td data-label="الفئة" class="py-3">
                                <span class="px-2 py-1 bg-white/10 text-stone-300 rounded text-xs">
                                    {{ exp.category || '—' }}
                                </span>
                            </td>
                            <td data-label="المبلغ" class="py-3 text-red-400 font-semibold">{{ fmt(exp.amount) }} ج</td>
                            <td data-label="التاريخ" class="py-3 text-stone-400">{{ formatDate(exp.date) }}</td>
                            <td data-label="" class="is-actions py-3">
                                <button
                                    v-if="$can('finance.manage')"
                                    type="button"
                                    class="p-1.5 text-stone-500 hover:text-red-400 hover:bg-red-500/10 rounded transition-colors"
                                    @click="confirmDeleteId = exp.id"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p
                    v-if="expenses.data.length === 0"
                    class="text-stone-400 text-center py-8 text-sm"
                >
                    لا توجد مصروفات مسجلة
                </p>
            </div>
        </div>
    </div>

    <ConfirmModal
        :is-open="confirmDeleteId !== null"
        title="حذف المصروف"
        message="هل أنت متأكد من رغبتك في حذف هذا المصروف؟ لا يمكن التراجع عن هذا الإجراء."
        confirm-text="نعم، احذف"
        cancel-text="إلغاء"
        variant="danger"
        @confirm="confirmDelete"
        @cancel="confirmDeleteId = null"
    />
</template>
