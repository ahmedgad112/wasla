<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, Bike, Lock, Save, User } from '@lucide/vue';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    vehicle_type: 'MOTORCYCLE',
    vehicle_plate: '',
});

const fieldClass = (hasError: boolean): string =>
    [
        'w-full p-3 text-sm rounded-xl border focus:outline-none focus:ring-2 focus:ring-orange-500 transition',
        hasError
            ? 'border-red-400 bg-red-50 dark:bg-red-950/20'
            : 'border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 text-stone-900 dark:text-white',
    ].join(' ');

const submit = (): void => {
    form.post('/restaurant/delivery-drivers');
};
</script>

<template>
    <Head title="إضافة كابتن — بوابة المطعم" />

    <div class="max-w-2xl space-y-6" dir="rtl">
        <div class="flex items-center gap-4">
            <Link
                href="/restaurant/delivery-drivers"
                class="p-2 rounded-xl bg-stone-100 dark:bg-stone-800 text-stone-500 hover:text-stone-900 dark:hover:text-white transition"
            >
                <ArrowRight class="w-5 h-5" />
            </Link>
            <div>
                <h1 class="text-xl font-black text-stone-900 dark:text-white">إضافة كابتن توصيل</h1>
                <p class="text-xs text-stone-500 mt-0.5">الحساب هيبقى تابع لمطعمك فقط</p>
            </div>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
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
                        <input v-model="form.name" type="text" required placeholder="محمد أحمد" :class="fieldClass(!!form.errors.name)">
                        <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1.5">
                            البريد الإلكتروني <span class="text-red-500">*</span>
                        </label>
                        <input v-model="form.email" type="email" required placeholder="driver@example.com" :class="fieldClass(!!form.errors.email)">
                        <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1.5">
                            رقم الهاتف <span class="text-red-500">*</span>
                        </label>
                        <input v-model="form.phone" type="tel" required placeholder="01xxxxxxxxx" :class="fieldClass(!!form.errors.phone)">
                        <p v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</p>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs">
                <h2 class="text-sm font-black text-stone-900 dark:text-white flex items-center gap-2 mb-4">
                    <Bike class="w-4 h-4 text-teal-500" />
                    المركبة
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1.5">نوع المركبة</label>
                        <select v-model="form.vehicle_type" :class="fieldClass(false)">
                            <option value="MOTORCYCLE">دراجة نارية</option>
                            <option value="SCOOTER">سكوتر</option>
                            <option value="CAR">سيارة</option>
                            <option value="BICYCLE">دراجة</option>
                            <option value="WALKING">مشياً</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1.5">رقم اللوحة</label>
                        <input v-model="form.vehicle_plate" type="text" placeholder="أ ب ج 1234" :class="fieldClass(!!form.errors.vehicle_plate)">
                        <p v-if="form.errors.vehicle_plate" class="text-red-500 text-xs mt-1">{{ form.errors.vehicle_plate }}</p>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs">
                <h2 class="text-sm font-black text-stone-900 dark:text-white flex items-center gap-2 mb-4">
                    <Lock class="w-4 h-4 text-purple-500" />
                    كلمة مرور الحساب
                </h2>
                <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1.5">
                    كلمة المرور <span class="text-red-500">*</span>
                </label>
                <input v-model="form.password" type="password" required placeholder="8 أحرف على الأقل" :class="fieldClass(!!form.errors.password)">
                <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
            </div>

            <div class="flex items-center justify-end gap-3">
                <Link
                    href="/restaurant/delivery-drivers"
                    class="px-5 py-2.5 rounded-xl border border-stone-200 dark:border-stone-700 text-stone-600 dark:text-stone-300 text-xs font-bold"
                >
                    إلغاء
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs disabled:opacity-50"
                >
                    <Save class="w-4 h-4" />
                    {{ form.processing ? 'جاري الإضافة...' : 'إضافة الكابتن' }}
                </button>
            </div>
        </form>
    </div>
</template>
