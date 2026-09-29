<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, FileText } from '@lucide/vue';

interface Restaurant {
    id: number;
    name: string;
}

defineProps<{
    restaurants: Restaurant[];
}>();

const form = useForm({
    restaurant_id: '',
    period_start: '',
    period_end: '',
    due_date: '',
    notes: '',
});

const submit = (): void => {
    form.post('/admin/invoices');
};
</script>

<template>
    <Head title="إنشاء فاتورة جديدة" />

    <div class="max-w-2xl" dir="rtl">
        <div class="flex items-center gap-4 mb-6">
            <Link
                href="/admin/invoices"
                class="p-2 rounded-lg bg-white hover:bg-stone-100 text-stone-400 hover:text-stone-900 transition-colors"
            >
                <ArrowLeft class="w-5 h-5" />
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-stone-900">إنشاء فاتورة جديدة</h1>
                <p class="text-stone-400 text-sm mt-1">فاتورة عمولة للمطعم</p>
            </div>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 flex items-center gap-2">
                    <FileText class="w-5 h-5 text-orange-400" />
                    بيانات الفاتورة
                </h2>

                <div>
                    <label class="block text-sm text-stone-400 mb-1">المطعم *</label>
                    <select
                        v-model="form.restaurant_id"
                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                    >
                        <option value="">اختر المطعم</option>
                        <option v-for="r in restaurants" :key="r.id" :value="r.id">{{ r.name }}</option>
                    </select>
                    <p v-if="form.errors.restaurant_id" class="text-red-400 text-xs mt-1">{{ form.errors.restaurant_id }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">بداية الفترة *</label>
                        <input
                            v-model="form.period_start"
                            type="date"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                        <p v-if="form.errors.period_start" class="text-red-400 text-xs mt-1">{{ form.errors.period_start }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">نهاية الفترة *</label>
                        <input
                            v-model="form.period_end"
                            type="date"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-stone-400 mb-1">تاريخ الاستحقاق</label>
                    <input
                        v-model="form.due_date"
                        type="date"
                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                    />
                </div>

                <div>
                    <label class="block text-sm text-stone-400 mb-1">ملاحظات</label>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        placeholder="ملاحظات إضافية للفاتورة..."
                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors resize-none"
                    />
                </div>
            </div>

            <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4">
                <p class="text-amber-300 text-sm">
                    💡 سيقوم النظام تلقائياً بحساب مبلغ الفاتورة بناءً على طلبات المطعم والعمولة المتفق عليها خلال الفترة المحددة.
                </p>
            </div>

            <div class="flex items-center justify-end gap-4">
                <Link
                    href="/admin/invoices"
                    class="px-6 py-2.5 bg-white hover:bg-stone-100 text-stone-600 rounded-lg font-medium border border-stone-200 transition-colors"
                >
                    إلغاء
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex items-center gap-2 px-6 py-2.5 bg-orange-500 hover:bg-orange-400 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                >
                    <Save class="w-4 h-4" />
                    {{ form.processing ? 'جاري الإنشاء...' : 'إنشاء الفاتورة' }}
                </button>
            </div>
        </form>
    </div>
</template>
