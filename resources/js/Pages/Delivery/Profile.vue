<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import DeliveryLayout from '../../Layouts/DeliveryLayout.vue';
import type { DeliveryDriver } from '../../Types';
import { Power, Store } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    driver: DeliveryDriver;
}>();

const isAvailable = computed(() => props.driver.availability_status === 'AVAILABLE');

const profileForm = useForm({
    phone: props.driver.phone || '',
});

const handleProfileSubmit = (): void => {
    profileForm.put('/delivery/profile');
};

const toggleAvailability = (): void => {
    router.post('/delivery/profile/availability', {
        availability_status: isAvailable.value ? 'OFFLINE' : 'AVAILABLE',
    });
};
</script>

<template>
    <DeliveryLayout title="حساب الطيار" :is-available="isAvailable">
        <Head title="حساب الطيار" />

        <div class="space-y-6">
            <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold text-2xl shadow-md">
                    {{ driver.name.charAt(0) }}
                </div>
                <div>
                    <h1 class="text-xl font-black text-stone-900 dark:text-white">{{ driver.name }}</h1>
                    <template v-if="driver.restaurant">
                        <p class="text-xs text-stone-500 mt-0.5">كابتن توصيل معتمد</p>
                        <p class="text-xs font-bold text-orange-600 dark:text-orange-400 mt-1 flex items-center gap-1">
                            <Store class="w-3.5 h-3.5" />
                            <span>تابع لمطعم: {{ driver.restaurant.name }}</span>
                        </p>
                    </template>
                    <p v-else class="text-xs text-stone-500 mt-0.5">كابتن توصيل</p>
                </div>
            </div>

            <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-black text-stone-900 dark:text-white">حالة الاتصال بالمنصة</h2>
                    <p class="text-xs text-stone-500 mt-0.5">
                        {{ isAvailable ? 'أنت متصل ومتاح لاستقبال مهام التوصيل' : 'أنت غير متصل (لن يتم إسناد طلبات لك)' }}
                    </p>
                </div>

                <button
                    type="button"
                    :class="[
                        'px-5 py-2.5 rounded-xl font-bold text-xs shadow transition flex items-center gap-1.5',
                        isAvailable ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-emerald-600 hover:bg-emerald-700 text-white',
                    ]"
                    @click="toggleAvailability"
                >
                    <Power class="w-3.5 h-3.5" />
                    <span>{{ isAvailable ? 'تحويل إلى غير متصل' : 'تفعيل الحضور (متاح)' }}</span>
                </button>
            </div>

            <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                <h2 class="text-sm font-black text-stone-900 dark:text-white mb-4">بيانات الاتصال</h2>
                <form class="space-y-4 max-w-md" @submit.prevent="handleProfileSubmit">
                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1">
                            رقم الهاتف للتواصل المباشر
                        </label>
                        <input
                            v-model="profileForm.phone"
                            type="tel"
                            required
                            class="w-full p-3 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-emerald-500"
                        />
                        <p v-if="profileForm.errors.phone" class="text-[11px] text-red-500 mt-1">{{ profileForm.errors.phone }}</p>
                    </div>

                    <button
                        type="submit"
                        :disabled="profileForm.processing"
                        class="py-2.5 px-6 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow transition"
                    >
                        حفظ التعديلات
                    </button>
                </form>
            </div>
        </div>
    </DeliveryLayout>
</template>
