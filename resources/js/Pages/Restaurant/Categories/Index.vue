<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import type { Restaurant, Category } from '../../../Types';
import { Plus, Trash2 } from '@lucide/vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';

defineProps<{
    categories?: (Category & { menu_items_count: number })[];
    restaurant: Restaurant;
}>();

const showModal = ref(false);
const confirmDelete = ref<number | null>(null);

const form = useForm({
    name: '',
    description: '',
    is_active: true,
});

const handleSubmit = (): void => {
    form.post('/restaurant/categories', {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
};

const handleDelete = (id: number): void => {
    confirmDelete.value = id;
};

const confirmDeleteAction = (): void => {
    if (confirmDelete.value) {
        router.delete(`/restaurant/categories/${confirmDelete.value}`);
    }
    confirmDelete.value = null;
};
</script>

<template>
    <Head title="تصنيفات المنيو — بوابة المطعم" />

    <div class="space-y-6">
        <div class="bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-6 shadow-xs">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-stone-100 dark:border-stone-800">
                <div>
                    <h1 class="text-xl font-black text-stone-900 dark:text-white">تصنيفات قائمة الطعام</h1>
                    <p class="text-xs text-stone-400 mt-0.5">تقسيم الوجبات لأقسام (فول وفلافل، ساندوتشات، مشروبات...)</p>
                </div>
                <button
                    type="button"
                    class="px-4 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs shadow transition flex items-center gap-1.5"
                    @click="showModal = true"
                >
                    <Plus class="w-4 h-4" />
                    <span>إضافة تصنيف جديد</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="cat in categories || []"
                    :key="cat.id"
                    class="p-5 rounded-2xl bg-stone-50 dark:bg-stone-800/60 border border-stone-200 dark:border-stone-700 flex items-center justify-between"
                >
                    <div>
                        <h3 class="font-extrabold text-sm text-stone-900 dark:text-white">{{ cat.name }}</h3>
                        <p class="text-xs text-stone-500 mt-0.5">{{ cat.menu_items_count || 0 }} صنف</p>
                    </div>
                    <button
                        type="button"
                        class="p-1.5 text-stone-400 hover:text-red-500 transition"
                        title="حذف"
                        @click="handleDelete(cat.id)"
                    >
                        <Trash2 class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-stone-950/70 backdrop-blur-sm" @click="showModal = false" />
        <div class="relative bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 w-full max-w-md p-6 shadow-2xl z-10 animate-fade-in">
            <h2 class="text-base font-black text-stone-900 dark:text-white mb-4">إضافة تصنيف جديد</h2>
            <form class="space-y-4" @submit.prevent="handleSubmit">
                <div>
                    <label class="block text-xs font-bold mb-1">اسم التصنيف</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        placeholder="مثال: فطير مشلتت بالسمن البلدي"
                        class="w-full p-2.5 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700"
                    />
                </div>
                <div>
                    <label class="block text-xs font-bold mb-1">الوصف (اختياري)</label>
                    <textarea
                        v-model="form.description"
                        rows="2"
                        class="w-full p-2.5 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700"
                    />
                </div>
                <div class="flex gap-2 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs"
                    >
                        حفظ التصنيف
                    </button>
                    <button
                        type="button"
                        class="py-2.5 px-4 rounded-xl border border-stone-200 dark:border-stone-700 text-xs font-bold"
                        @click="showModal = false"
                    >
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>

    <ConfirmModal
        :is-open="confirmDelete !== null"
        message="هل أنت متأكد من حذف هذا التصنيف؟ لن تتمكن من استعادته."
        @confirm="confirmDeleteAction"
        @cancel="confirmDelete = null"
    />
</template>
