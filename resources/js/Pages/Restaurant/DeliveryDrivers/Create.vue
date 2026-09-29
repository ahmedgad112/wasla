<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, User, Car } from '@lucide/vue';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    vehicle_type: 'MOTORCYCLE',
    vehicle_plate: '',
    national_id: '',
});

const handleSubmit = (): void => {
    form.post('/restaurant/delivery-drivers');
};
</script>

<template>
    <Head title="إضافة سائق توصيل" />

    <div class="max-w-2xl" dir="rtl">
        <div class="flex items-center gap-4 mb-6">
            <Link
                href="/restaurant/delivery-drivers"
                class="p-2 rounded-lg bg-white hover:bg-stone-100 text-stone-400 hover:text-stone-900 transition-colors"
            >
                <ArrowLeft class="w-5 h-5" />
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-stone-900">إضافة سائق توصيل</h1>
                <p class="text-stone-400 text-sm mt-1">أضف سائقاً جديداً لفريق التوصيل</p>
            </div>
        </div>

        <form class="space-y-6" @submit.prevent="handleSubmit">
            <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 flex items-center gap-2">
                    <User class="w-5 h-5 text-orange-400" />
                    البيانات الشخصية
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm text-stone-400 mb-1">الاسم الكامل *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="محمد أحمد"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                        <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">البريد الإلكتروني *</label>
                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="driver@example.com"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                        <p v-if="form.errors.email" class="text-red-400 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">رقم الهاتف *</label>
                        <input
                            v-model="form.phone"
                            type="text"
                            placeholder="01xxxxxxxxx"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                        <p v-if="form.errors.phone" class="text-red-400 text-xs mt-1">{{ form.errors.phone }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">كلمة المرور *</label>
                        <input
                            v-model="form.password"
                            type="password"
                            placeholder="••••••••"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                        <p v-if="form.errors.password" class="text-red-400 text-xs mt-1">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">الرقم القومي</label>
                        <input
                            v-model="form.national_id"
                            type="text"
                            placeholder="xxxxxxxxxxxxxxxxxxx"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                    </div>
                </div>
            </div>

            <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 flex items-center gap-2">
                    <Car class="w-5 h-5 text-indigo-400" />
                    بيانات المركبة
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">نوع المركبة</label>
                        <select
                            v-model="form.vehicle_type"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                        >
                            <option value="MOTORCYCLE">دراجة نارية</option>
                            <option value="CAR">سيارة</option>
                            <option value="BICYCLE">دراجة هوائية</option>
                            <option value="WALKING">مشياً</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">رقم اللوحة</label>
                        <input
                            v-model="form.vehicle_plate"
                            type="text"
                            placeholder="أ ب ج 1234"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-4">
                <Link
                    href="/restaurant/delivery-drivers"
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
                    {{ form.processing ? 'جاري الإضافة...' : 'إضافة السائق' }}
                </button>
            </div>
        </form>
    </div>
</template>
