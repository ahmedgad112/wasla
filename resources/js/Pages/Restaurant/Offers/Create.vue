<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Tag, Calendar } from '@lucide/vue';

defineProps<{
    menuItems: { id: number; name: string }[];
}>();

const form = useForm({
    title: '',
    description: '',
    discount_type: 'PERCENTAGE',
    discount_value: '',
    min_order_amount: '',
    start_date: '',
    end_date: '',
    is_active: true,
    is_student_only: false,
    menu_item_id: '',
});

const handleSubmit = (): void => {
    form.post('/restaurant/offers');
};
</script>

<template>
    <Head title="إضافة عرض جديد" />

    <div class="max-w-2xl" dir="rtl">
        <div class="flex items-center gap-4 mb-6">
            <Link
                href="/restaurant/offers"
                class="p-2 rounded-lg bg-white hover:bg-stone-100 text-stone-400 hover:text-stone-900 transition-colors"
            >
                <ArrowLeft class="w-5 h-5" />
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-stone-900">إضافة عرض جديد</h1>
                <p class="text-stone-400 text-sm mt-1">أنشئ عرضاً ترويجياً لمطعمك</p>
            </div>
        </div>

        <form class="space-y-6" @submit.prevent="handleSubmit">
            <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 flex items-center gap-2">
                    <Tag class="w-5 h-5 text-orange-400" />
                    تفاصيل العرض
                </h2>

                <div>
                    <label class="block text-sm text-stone-400 mb-1">عنوان العرض *</label>
                    <input
                        v-model="form.title"
                        type="text"
                        placeholder="خصم 20% على كل الفطائر"
                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors"
                    />
                    <p v-if="form.errors.title" class="text-red-400 text-xs mt-1">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label class="block text-sm text-stone-400 mb-1">الوصف</label>
                    <textarea
                        v-model="form.description"
                        rows="2"
                        placeholder="وصف مختصر للعرض..."
                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors resize-none"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">نوع الخصم</label>
                        <select
                            v-model="form.discount_type"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                        >
                            <option value="PERCENTAGE">نسبة مئوية (%)</option>
                            <option value="FIXED">مبلغ ثابت (ج.م)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm text-stone-400 mb-1">
                            قيمة الخصم {{ form.discount_type === 'PERCENTAGE' ? '(%)' : '(ج.م)' }} *
                        </label>
                        <input
                            v-model="form.discount_value"
                            type="number"
                            step="0.01"
                            min="0"
                            :placeholder="form.discount_type === 'PERCENTAGE' ? '20' : '10.00'"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                        <p v-if="form.errors.discount_value" class="text-red-400 text-xs mt-1">{{ form.errors.discount_value }}</p>
                    </div>

                    <div>
                        <label class="block text-sm text-stone-400 mb-1">الحد الأدنى للطلب (ج.م)</label>
                        <input
                            v-model="form.min_order_amount"
                            type="number"
                            step="0.01"
                            placeholder="0.00 (لا يوجد حد أدنى)"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                    </div>

                    <div>
                        <label class="block text-sm text-stone-400 mb-1">الطبق المرتبط (اختياري)</label>
                        <select
                            v-model="form.menu_item_id"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                        >
                            <option value="">عرض عام</option>
                            <option v-for="item in menuItems" :key="item.id" :value="item.id">{{ item.name }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 flex items-center gap-2">
                    <Calendar class="w-5 h-5 text-indigo-400" />
                    مدة العرض
                </h2>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">تاريخ البداية</label>
                        <input
                            v-model="form.start_date"
                            type="date"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                    </div>
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">تاريخ الانتهاء</label>
                        <input
                            v-model="form.end_date"
                            type="date"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                    </div>
                </div>

                <div class="space-y-3 pt-2">
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input v-model="form.is_active" type="checkbox" class="w-4 h-4 accent-orange-500 rounded" />
                        <span class="text-sm font-semibold text-stone-200">تفعيل العرض فوراً للجمهور</span>
                    </label>

                    <label class="flex items-start gap-2.5 p-3.5 rounded-xl bg-orange-500/10 border border-orange-500/30 cursor-pointer">
                        <input v-model="form.is_student_only" type="checkbox" class="w-4 h-4 mt-0.5 accent-orange-500 rounded" />
                        <div>
                            <span class="text-xs font-bold text-orange-400 block">
                                🎓 عرض حصري لطلاب الجامعات الموثقين فقط
                            </span>
                            <span class="text-[11px] text-stone-400 block mt-0.5">
                                ينطبق هذا العرض فقط على الطلاب الذين رفعوا كارنيهاتهم وتم اعتمادها وتوثيقها من إدارة المنصة.
                            </span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-4">
                <Link
                    href="/restaurant/offers"
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
                    {{ form.processing ? 'جاري الإنشاء...' : 'إنشاء العرض' }}
                </button>
            </div>
        </form>
    </div>
</template>
