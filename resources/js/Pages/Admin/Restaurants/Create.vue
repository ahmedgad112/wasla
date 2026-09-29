<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Store, DollarSign, User, Bike } from '@lucide/vue';

const form = useForm({
    name: '',
    description: '',
    address: '',
    phone: '',
    email: '',
    commission_rate: 15,
    delivery_provider: 'RESTAURANT' as 'PLATFORM' | 'RESTAURANT' | 'PICKUP',
    delivery_fee: 10,
    delivery_base_fee: 10,
    delivery_fee_per_km: 5,
    owner_name: '',
    owner_email: '',
    owner_password: '',
});

const handleSubmit = (): void => {
    form.post('/admin/restaurants');
};
</script>

<template>
    <Head title="إضافة مطعم جديد" />

    <div class="max-w-4xl" dir="rtl">
        <div class="mb-6 flex items-center gap-4">
            <Link
                href="/admin/restaurants"
                class="rounded-xl border border-stone-200 bg-white p-2 text-stone-500 transition-colors hover:bg-stone-50 hover:text-stone-900"
            >
                <ArrowLeft class="h-5 w-5" />
            </Link>
            <div>
                <h1 class="text-2xl font-black text-stone-900">إضافة مطعم جديد</h1>
                <p class="mt-1 text-sm text-stone-500">أدخل بيانات المطعم وحساب المالك</p>
            </div>
        </div>

        <div
            v-if="Object.keys(form.errors).length > 0"
            class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-600"
        >
            راجع الحقول المطلوبة — في أخطاء لازم تتصلح قبل الإنشاء.
        </div>

        <form class="space-y-6" @submit.prevent="handleSubmit">
            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-xs">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-stone-900">
                    <Store class="h-5 w-5 text-orange-500" />
                    بيانات المطعم
                </h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-bold text-stone-600">اسم المطعم *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="مطعم الأصالة"
                            class="w-full rounded-lg border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:border-orange-500 focus:outline-none"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-bold text-stone-600">الوصف</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            placeholder="وصف مختصر للمطعم وأشهر أطباقه..."
                            class="w-full resize-none rounded-lg border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:border-orange-500 focus:outline-none"
                        />
                        <p v-if="form.errors.description" class="mt-1 text-xs text-red-500">{{ form.errors.description }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-stone-600">رقم الهاتف *</label>
                        <input
                            v-model="form.phone"
                            type="text"
                            required
                            placeholder="01xxxxxxxxx"
                            class="w-full rounded-lg border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:border-orange-500 focus:outline-none"
                        />
                        <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500">{{ form.errors.phone }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-stone-600">البريد الإلكتروني</label>
                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="restaurant@example.com"
                            class="w-full rounded-lg border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:border-orange-500 focus:outline-none"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-bold text-stone-600">العنوان *</label>
                        <input
                            v-model="form.address"
                            type="text"
                            required
                            placeholder="برج العرب، الإسكندرية"
                            class="w-full rounded-lg border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:border-orange-500 focus:outline-none"
                        />
                        <p v-if="form.errors.address" class="mt-1 text-xs text-red-500">{{ form.errors.address }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-xs">
                <h2 class="mb-2 flex items-center gap-2 text-lg font-semibold text-stone-900">
                    <Bike class="h-5 w-5 text-sky-500" />
                    طريقة التوصيل / الاستلام
                </h2>
                <p class="mb-4 text-xs text-stone-500">
                    حدّد هل الطلب هيتحوّل بالتوصيل (من المطعم أو الموقع) ولا الاستلام من المطعم.
                </p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <button
                        type="button"
                        :class="[
                            'rounded-2xl border-2 p-4 text-right transition',
                            form.delivery_provider === 'RESTAURANT'
                                ? 'border-orange-500 bg-orange-50 text-orange-900'
                                : 'border-stone-200 bg-stone-50 text-stone-600 hover:border-stone-300',
                        ]"
                        @click="form.delivery_provider = 'RESTAURANT'"
                    >
                        <p class="text-sm font-black">توصيل من المطعم</p>
                        <p class="mt-1 text-[11px] opacity-80">المطعم يقدر يغيّر السعر ويحدد لو التوصيل متاح أو لأ.</p>
                    </button>
                    <button
                        type="button"
                        :class="[
                            'rounded-2xl border-2 p-4 text-right transition',
                            form.delivery_provider === 'PLATFORM'
                                ? 'border-sky-500 bg-sky-50 text-sky-900'
                                : 'border-stone-200 bg-stone-50 text-stone-600 hover:border-stone-300',
                        ]"
                        @click="form.delivery_provider = 'PLATFORM'"
                    >
                        <p class="text-sm font-black">توصيل من الموقع</p>
                        <p class="mt-1 text-[11px] opacity-80">المنصة تتحكم في السعر — المطعم مش هيقدر يعدّله.</p>
                    </button>
                    <button
                        type="button"
                        :class="[
                            'rounded-2xl border-2 p-4 text-right transition',
                            form.delivery_provider === 'PICKUP'
                                ? 'border-emerald-500 bg-emerald-50 text-emerald-900'
                                : 'border-stone-200 bg-stone-50 text-stone-600 hover:border-stone-300',
                        ]"
                        @click="form.delivery_provider = 'PICKUP'"
                    >
                        <p class="text-sm font-black">استلام من المطعم</p>
                        <p class="mt-1 text-[11px] opacity-80">العميل يستلم بنفسه — مفيش رسوم توصيل.</p>
                    </button>
                </div>
                <p v-if="form.errors.delivery_provider" class="mt-2 text-xs text-red-500">{{ form.errors.delivery_provider }}</p>

                <div v-if="form.delivery_provider === 'PLATFORM'" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm font-bold text-stone-600">رسوم التوصيل (ج.م)</label>
                        <input
                            v-model.number="form.delivery_fee"
                            type="number"
                            min="0"
                            step="0.5"
                            class="w-full rounded-lg border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 focus:border-orange-500 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-stone-600">سعر فتح العداد (ج.م)</label>
                        <input
                            v-model.number="form.delivery_base_fee"
                            type="number"
                            min="0"
                            step="0.5"
                            class="w-full rounded-lg border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 focus:border-orange-500 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-stone-600">سعر الكيلو (ج.م)</label>
                        <input
                            v-model.number="form.delivery_fee_per_km"
                            type="number"
                            min="0"
                            step="0.5"
                            class="w-full rounded-lg border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 focus:border-orange-500 focus:outline-none"
                        />
                    </div>
                </div>
                <p
                    v-else-if="form.delivery_provider === 'PICKUP'"
                    class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-800"
                >
                    الاستلام من المطعم فقط — مش هيظهر سعر توصيل للعميل.
                </p>
            </div>

            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-xs">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-stone-900">
                    <User class="h-5 w-5 text-indigo-500" />
                    حساب المالك
                </h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm font-bold text-stone-600">الاسم *</label>
                        <input
                            v-model="form.owner_name"
                            type="text"
                            required
                            placeholder="أحمد محمد"
                            class="w-full rounded-lg border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:border-orange-500 focus:outline-none"
                        />
                        <p v-if="form.errors.owner_name" class="mt-1 text-xs text-red-500">{{ form.errors.owner_name }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-stone-600">البريد الإلكتروني *</label>
                        <input
                            v-model="form.owner_email"
                            type="email"
                            required
                            placeholder="owner@example.com"
                            class="w-full rounded-lg border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:border-orange-500 focus:outline-none"
                        />
                        <p v-if="form.errors.owner_email" class="mt-1 text-xs text-red-500">{{ form.errors.owner_email }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-stone-600">كلمة المرور *</label>
                        <input
                            v-model="form.owner_password"
                            type="password"
                            required
                            minlength="8"
                            placeholder="••••••••"
                            class="w-full rounded-lg border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:border-orange-500 focus:outline-none"
                        />
                        <p v-if="form.errors.owner_password" class="mt-1 text-xs text-red-500">{{ form.errors.owner_password }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-xs">
                <h2 class="mb-2 flex items-center gap-2 text-lg font-semibold text-stone-900">
                    <DollarSign class="h-5 w-5 text-amber-500" />
                    الإعدادات المالية
                </h2>
                <p class="mb-4 text-xs text-stone-500">
                    أوقات العمل يضبطها المطعم من إعدادات الفرع بعد الإنشاء.
                    <span v-if="form.delivery_provider === 'RESTAURANT'"> رسوم التوصيل يضبطها المطعم أيضاً.</span>
                    <span v-else-if="form.delivery_provider === 'PLATFORM'"> رسوم التوصيل ثابتة من المنصة ولا يقدر المطعم يغيّرها.</span>
                    <span v-else> الطلب للاستلام من المطعم بدون توصيل.</span>
                </p>
                <div class="max-w-xs">
                    <label class="mb-1 block text-sm font-bold text-stone-600">نسبة العمولة (%)</label>
                    <input
                        v-model.number="form.commission_rate"
                        type="number"
                        step="0.1"
                        min="0"
                        max="100"
                        class="w-full rounded-lg border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 focus:border-orange-500 focus:outline-none"
                    />
                    <p v-if="form.errors.commission_rate" class="mt-1 text-xs text-red-500">{{ form.errors.commission_rate }}</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-4">
                <Link
                    href="/admin/restaurants"
                    class="rounded-xl border border-stone-200 bg-white px-6 py-2.5 font-bold text-stone-600 transition-colors hover:bg-stone-50"
                >
                    إلغاء
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex items-center gap-2 rounded-xl bg-orange-500 px-6 py-2.5 font-bold text-white transition-colors hover:bg-orange-400 disabled:opacity-50"
                >
                    <Save class="h-4 w-4" />
                    {{ form.processing ? 'جاري الإنشاء...' : 'إنشاء المطعم' }}
                </button>
            </div>
        </form>
    </div>
</template>
