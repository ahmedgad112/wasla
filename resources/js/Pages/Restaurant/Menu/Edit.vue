<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from '@lucide/vue';

interface MenuItem {
    id: number;
    name: string;
    description: string | null;
    price: number;
    category_id: number;
    is_available: boolean;
    is_featured: boolean;
    calories: number | null;
    preparation_time: number | null;
    sort_order: number;
}

const props = defineProps<{
    menuItem: MenuItem;
    categories: { id: number; name: string }[];
}>();

const form = useForm({
    name: props.menuItem.name,
    description: props.menuItem.description ?? '',
    price: (props.menuItem.price / 100).toFixed(2),
    category_id: String(props.menuItem.category_id),
    is_available: props.menuItem.is_available,
    is_featured: props.menuItem.is_featured,
    calories: props.menuItem.calories ? String(props.menuItem.calories) : '',
    preparation_time: props.menuItem.preparation_time ? String(props.menuItem.preparation_time) : '',
    sort_order: props.menuItem.sort_order,
});

const handleSubmit = (): void => {
    form.put(`/restaurant/menu/${props.menuItem.id}`);
};
</script>

<template>
    <Head :title="`تعديل ${menuItem.name}`" />

    <div class="max-w-2xl" dir="rtl">
        <div class="flex items-center gap-4 mb-6">
            <Link
                href="/restaurant/menu"
                class="p-2 rounded-lg bg-white hover:bg-stone-100 text-stone-400 hover:text-stone-900 transition-colors"
            >
                <ArrowLeft class="w-5 h-5" />
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-stone-900">تعديل الطبق</h1>
                <p class="text-stone-400 text-sm mt-1">{{ menuItem.name }}</p>
            </div>
        </div>

        <form class="space-y-6" @submit.prevent="handleSubmit">
            <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900">بيانات الطبق</h2>

                <div>
                    <label class="block text-sm text-stone-400 mb-1">اسم الطبق *</label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                    />
                    <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm text-stone-400 mb-1">الوصف</label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors resize-none"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">السعر (ج.م) *</label>
                        <input
                            v-model="form.price"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                    </div>

                    <div>
                        <label class="block text-sm text-stone-400 mb-1">الفئة *</label>
                        <select
                            v-model="form.category_id"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                        >
                            <option value="">اختر الفئة</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm text-stone-400 mb-1">السعرات الحرارية</label>
                        <input
                            v-model="form.calories"
                            type="number"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                    </div>

                    <div>
                        <label class="block text-sm text-stone-400 mb-1">وقت التحضير (دقيقة)</label>
                        <input
                            v-model="form.preparation_time"
                            type="number"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                    </div>

                    <div>
                        <label class="block text-sm text-stone-400 mb-1">الترتيب</label>
                        <input
                            v-model.number="form.sort_order"
                            type="number"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-6 pt-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="form.is_available" type="checkbox" class="w-4 h-4 accent-orange-500" />
                        <span class="text-sm text-stone-300">متاح للطلب</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="form.is_featured" type="checkbox" class="w-4 h-4 accent-orange-500" />
                        <span class="text-sm text-stone-300">طبق مميز</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-4">
                <Link
                    href="/restaurant/menu"
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
                    {{ form.processing ? 'جاري الحفظ...' : 'حفظ التغييرات' }}
                </button>
            </div>
        </form>
    </div>
</template>
