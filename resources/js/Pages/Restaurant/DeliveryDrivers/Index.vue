<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import type { Restaurant, DeliveryDriver, PaginatedResponse } from '../../../Types';
import { Bike, Plus, Trash2 } from '@lucide/vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';

const props = defineProps<{
    drivers: PaginatedResponse<DeliveryDriver>;
    restaurant: Restaurant;
}>();

const items = computed(() => props.drivers?.data || []);
const showModal = ref(false);
const confirmDelete = ref<number | null>(null);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
});

const handleSubmit = (): void => {
    form.post('/restaurant/delivery-drivers', {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        },
    });
};

const handleToggleStatus = (id: number): void => {
    router.patch(`/restaurant/delivery-drivers/${id}/toggle-status`);
};

const handleDelete = (id: number): void => {
    confirmDelete.value = id;
};

const confirmDeleteAction = (): void => {
    if (confirmDelete.value) {
        router.delete(`/restaurant/delivery-drivers/${confirmDelete.value}`);
    }
    confirmDelete.value = null;
};
</script>

<template>
    <Head title="كباتن التوصيل — بوابة المطعم" />

    <div class="space-y-6">
        <div class="bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-6 shadow-xs">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-stone-100 dark:border-stone-800">
                <div>
                    <h1 class="text-xl font-black text-stone-900 dark:text-white">طيارو وكباتن المطعم</h1>
                    <p class="text-xs text-stone-400 mt-0.5">
                        إدارة فريق التوصيل الخاص بـ <span class="font-bold text-orange-500">{{ restaurant.name }}</span> وحالات الاتصال
                    </p>
                </div>
                <button
                    type="button"
                    class="px-4 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs shadow transition flex items-center gap-1.5"
                    @click="showModal = true"
                >
                    <Plus class="w-4 h-4" />
                    <span>إضافة كابتن جديد</span>
                </button>
            </div>

            <div v-if="items.length === 0" class="text-center py-12">
                <Bike class="w-12 h-12 text-stone-300 dark:text-stone-600 mx-auto mb-2" />
                <p class="text-xs text-stone-400">لا يوجد طيارون مسجلون في المطعم حالياً.</p>
            </div>
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="d in items"
                    :key="d.id"
                    class="p-5 rounded-2xl bg-stone-50 dark:bg-stone-800/60 border border-stone-200 dark:border-stone-700 space-y-3"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-teal-600 text-white flex items-center justify-center font-bold text-sm">
                                {{ d.name.charAt(0) }}
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-stone-900 dark:text-white">{{ d.name }}</h3>
                                <p class="text-xs font-mono text-stone-500">{{ d.phone }}</p>
                                <p class="text-[10px] text-orange-500 font-bold mt-0.5">تابع لـ {{ restaurant.name }}</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="text-stone-400 hover:text-red-500 p-1"
                            title="حذف"
                            @click="handleDelete(d.id)"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="pt-2 border-t border-stone-200 dark:border-stone-700 flex items-center justify-between text-xs">
                        <span
                            :class="[
                                'px-2.5 py-0.5 rounded-full text-[10px] font-bold',
                                d.availability_status === 'AVAILABLE' ? 'bg-emerald-100 text-emerald-700' : 'bg-stone-200 text-stone-600',
                            ]"
                        >
                            {{ d.availability_status === 'AVAILABLE' ? 'متصل ومتاح' : 'غير متصل' }}
                        </span>

                        <button
                            type="button"
                            class="text-stone-500 hover:text-stone-900 dark:hover:text-white underline text-[11px]"
                            @click="handleToggleStatus(d.id)"
                        >
                            {{ d.is_active ? 'تعطيل الحساب' : 'تفعيل الحساب' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-stone-950/70 backdrop-blur-sm" @click="showModal = false" />
        <div class="relative bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 w-full max-w-md p-6 shadow-2xl z-10 animate-fade-in">
            <h2 class="text-base font-black text-stone-900 dark:text-white mb-4">إضافة كابتن توصيل للمطعم</h2>
            <form class="space-y-4" @submit.prevent="handleSubmit">
                <div>
                    <label class="block text-xs font-bold mb-1">اسم الكابتن</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full p-2.5 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700"
                    />
                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-xs font-bold mb-1">البريد الإلكتروني لتسجيل الدخول</label>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        class="w-full p-2.5 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700"
                    />
                    <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="block text-xs font-bold mb-1">رقم الهاتف</label>
                    <input
                        v-model="form.phone"
                        type="tel"
                        required
                        class="w-full p-2.5 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700"
                    />
                    <p v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</p>
                </div>

                <div>
                    <label class="block text-xs font-bold mb-1">كلمة المرور</label>
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        class="w-full p-2.5 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700"
                    />
                    <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
                </div>

                <div class="flex gap-2 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs"
                    >
                        إضافة الكابتن
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
        message="هل أنت متأكد من حذف هذا الكابتن؟ لن تتمكن من استعادته."
        @confirm="confirmDeleteAction"
        @cancel="confirmDelete = null"
    />
</template>
