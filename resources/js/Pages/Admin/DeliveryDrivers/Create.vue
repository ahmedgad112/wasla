<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, Save, User, Store, Lock } from '@lucide/vue';
import type { Restaurant } from '../../../Types';

const props = defineProps<{
    restaurants: Restaurant[];
}>();

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    restaurant_id: '',
});

const selectedRestaurantName = computed(
    () => props.restaurants.find((r) => String(r.id) === form.restaurant_id)?.name,
);

const submit = (): void => {
    form.post('/admin/delivery-drivers');
};
</script>

<template>
    <Head title="إضافة مندوب — لوحة الإدارة" />

    <div class="max-w-2xl space-y-6" dir="rtl">
        <div class="flex items-center gap-4">
            <Link
                href="/admin/delivery-drivers"
                class="p-2 rounded-xl bg-stone-100 dark:bg-stone-800 text-stone-500 hover:text-stone-900 dark:hover:text-white transition"
            >
                <ArrowRight class="w-5 h-5" />
            </Link>
            <div>
                <h1 class="text-xl font-black text-stone-900 dark:text-white">إضافة مندوب توصيل جديد</h1>
                <p class="text-xs text-stone-500 mt-0.5">أنشئ حساب مندوب وحدد المطعم التابع له</p>
            </div>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div class="p-6 bg-white dark:bg-stone-900 rounded-2xl border border-orange-200 dark:border-orange-900 shadow-xs">
                <h2 class="text-sm font-black text-stone-900 dark:text-white flex items-center gap-2 mb-4">
                    <Store class="w-4 h-4 text-orange-500" />
                    المطعم التابع له المندوب
                    <span class="text-red-500 text-xs">*</span>
                </h2>
                <p class="text-xs text-stone-500 mb-3">
                    يجب تحديد مطعم واحد فقط. المندوب سيكون حصرياً تابعاً لهذا المطعم ولن يظهر في أي مطعم آخر.
                </p>
                <select
                    v-model="form.restaurant_id"
                    required
                    :class="[
                        'w-full p-3 text-sm rounded-xl border focus:outline-none focus:ring-2 focus:ring-orange-500 transition',
                        form.errors.restaurant_id
                            ? 'border-red-400 bg-red-50 dark:bg-red-950/20'
                            : 'border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 text-stone-900 dark:text-white',
                    ]"
                >
                    <option value="">— اختر المطعم —</option>
                    <option v-for="r in restaurants" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
                <p v-if="form.errors.restaurant_id" class="text-red-500 text-xs mt-1.5 flex items-center gap-1">
                    <span>⚠</span> {{ form.errors.restaurant_id }}
                </p>
                <p v-if="form.restaurant_id" class="text-emerald-600 dark:text-emerald-400 text-xs mt-2 font-bold">
                    ✓ سيكون المندوب تابعاً لـ: {{ selectedRestaurantName }}
                </p>
            </div>

            <div class="p-6 bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs">
                <h2 class="text-sm font-black text-stone-900 dark:text-white flex items-center gap-2 mb-4">
                    <User class="w-4 h-4 text-blue-500" />
                    البيانات الشخصية
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1.5">
                            الاسم الكامل <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="محمد أحمد"
                            :class="[
                                'w-full p-3 text-sm rounded-xl border focus:outline-none focus:ring-2 focus:ring-orange-500 transition',
                                form.errors.name
                                    ? 'border-red-400 bg-red-50 dark:bg-red-950/20'
                                    : 'border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 text-stone-900 dark:text-white',
                            ]"
                        />
                        <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1.5">
                            البريد الإلكتروني <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            placeholder="driver@example.com"
                            :class="[
                                'w-full p-3 text-sm rounded-xl border focus:outline-none focus:ring-2 focus:ring-orange-500 transition',
                                form.errors.email
                                    ? 'border-red-400 bg-red-50 dark:bg-red-950/20'
                                    : 'border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 text-stone-900 dark:text-white',
                            ]"
                        />
                        <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1.5">
                            رقم الهاتف <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.phone"
                            type="tel"
                            required
                            placeholder="01xxxxxxxxx"
                            :class="[
                                'w-full p-3 text-sm rounded-xl border focus:outline-none focus:ring-2 focus:ring-orange-500 transition',
                                form.errors.phone
                                    ? 'border-red-400 bg-red-50 dark:bg-red-950/20'
                                    : 'border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 text-stone-900 dark:text-white',
                            ]"
                        />
                        <p v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</p>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs">
                <h2 class="text-sm font-black text-stone-900 dark:text-white flex items-center gap-2 mb-4">
                    <Lock class="w-4 h-4 text-purple-500" />
                    كلمة مرور الحساب
                </h2>
                <div>
                    <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1.5">
                        كلمة المرور <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        placeholder="8 أحرف على الأقل"
                        :class="[
                            'w-full p-3 text-sm rounded-xl border focus:outline-none focus:ring-2 focus:ring-orange-500 transition',
                            form.errors.password
                                ? 'border-red-400 bg-red-50 dark:bg-red-950/20'
                                : 'border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 text-stone-900 dark:text-white',
                        ]"
                    />
                    <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <Link
                    href="/admin/delivery-drivers"
                    class="px-5 py-2.5 rounded-xl border border-stone-200 dark:border-stone-700 text-stone-600 dark:text-stone-300 text-xs font-bold hover:bg-stone-50 dark:hover:bg-stone-800 transition"
                >
                    إلغاء
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing || !form.restaurant_id"
                    class="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs shadow transition disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <Save class="w-4 h-4" />
                    {{ form.processing ? 'جاري الإنشاء...' : 'إنشاء الحساب' }}
                </button>
            </div>
        </form>
    </div>
</template>
