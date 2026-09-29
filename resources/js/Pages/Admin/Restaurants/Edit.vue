<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ArrowLeft, Save, Store, DollarSign, Clock, Bike } from '@lucide/vue';

interface Restaurant {
    id: number;
    name: string;
    description: string;
    address: string;
    phone: string;
    email: string | null;
    status: string;
    opening_time: string;
    closing_time: string;
    delivery_fee: number;
    delivery_base_fee?: number;
    delivery_fee_per_km?: number;
    delivery_provider?: 'PLATFORM' | 'RESTAURANT' | 'PICKUP';
    delivery_enabled?: boolean;
    minimum_order_amount: number;
    estimated_delivery_time: number;
    commission_type?: string;
    commission_percentage?: number;
    monthly_subscription_fee?: number;
    student_discount_percentage?: number;
}

const props = defineProps<{
    restaurant: Restaurant;
}>();

function toTimeInput(value?: string | null): string {
    if (!value) {
        return '08:00';
    }
    return value.slice(0, 5);
}

const form = useForm({
    name: props.restaurant.name,
    description: props.restaurant.description ?? '',
    address: props.restaurant.address,
    phone: props.restaurant.phone,
    email: props.restaurant.email ?? '',
    status: props.restaurant.status,
    opening_time: toTimeInput(props.restaurant.opening_time),
    closing_time: toTimeInput(props.restaurant.closing_time),
    delivery_provider: props.restaurant.delivery_provider ?? 'RESTAURANT',
    delivery_enabled: props.restaurant.delivery_enabled !== false,
    delivery_fee: Number(props.restaurant.delivery_fee ?? 0),
    delivery_base_fee: Number(props.restaurant.delivery_base_fee ?? props.restaurant.delivery_fee ?? 10),
    delivery_fee_per_km: Number(props.restaurant.delivery_fee_per_km ?? 5),
    minimum_order_amount: Number(props.restaurant.minimum_order_amount ?? 0),
    estimated_delivery_time: props.restaurant.estimated_delivery_time ?? 30,
    commission_type: props.restaurant.commission_type ?? 'PERCENTAGE',
    commission_percentage: Number(props.restaurant.commission_percentage ?? 15),
    monthly_subscription_fee: Number(props.restaurant.monthly_subscription_fee ?? 0),
    student_discount_percentage: Number(props.restaurant.student_discount_percentage ?? 0),
});

const handleSubmit = (): void => {
    form.put(`/admin/restaurants/${props.restaurant.id}`);
};
</script>

<template>
    <Head :title="`تعديل ${restaurant.name}`" />

    <div class="max-w-4xl" dir="rtl">
        <div class="mb-6 flex items-center gap-4">
            <Link
                :href="`/admin/restaurants/${restaurant.id}`"
                class="rounded-lg bg-white p-2 text-stone-400 transition-colors hover:bg-stone-100 hover:text-stone-900"
            >
                <ArrowLeft class="h-5 w-5" />
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-stone-900">تعديل المطعم</h1>
                <p class="mt-1 text-sm text-stone-400">{{ restaurant.name }}</p>
            </div>
        </div>

        <form class="space-y-6" @submit.prevent="handleSubmit">
            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-xs">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-stone-900">
                    <Store class="h-5 w-5 text-orange-400" />
                    المعلومات الأساسية
                </h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm text-stone-400">اسم المطعم *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-stone-900 transition-colors focus:border-orange-500 focus:outline-none"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-400">{{ form.errors.name }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm text-stone-400">الوصف</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full resize-none rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-stone-900 transition-colors focus:border-orange-500 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-stone-400">رقم الهاتف *</label>
                        <input
                            v-model="form.phone"
                            type="text"
                            class="w-full rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-stone-900 transition-colors focus:border-orange-500 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-stone-400">البريد الإلكتروني</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-stone-900 transition-colors focus:border-orange-500 focus:outline-none"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm text-stone-400">العنوان *</label>
                        <input
                            v-model="form.address"
                            type="text"
                            class="w-full rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-stone-900 transition-colors focus:border-orange-500 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-stone-400">الحالة</label>
                        <select
                            v-model="form.status"
                            class="w-full rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-stone-900 transition-colors focus:border-orange-500 focus:outline-none"
                        >
                            <option value="ACTIVE">نشط</option>
                            <option value="SUSPENDED">معلق</option>
                            <option value="PENDING">قيد المراجعة</option>
                            <option value="INACTIVE">غير نشط</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-xs">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-stone-900">
                    <Bike class="h-5 w-5 text-sky-500" />
                    طريقة التوصيل / الاستلام
                </h2>
                <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <button
                        type="button"
                        :class="[
                            'rounded-2xl border-2 p-4 text-right transition',
                            form.delivery_provider === 'RESTAURANT'
                                ? 'border-orange-500 bg-orange-50 text-orange-900'
                                : 'border-stone-200 bg-stone-50 text-stone-600',
                        ]"
                        @click="form.delivery_provider = 'RESTAURANT'"
                    >
                        <p class="text-sm font-black">توصيل من المطعم</p>
                        <p class="mt-1 text-[11px] opacity-80">المطعم يتحكم في السعر وتوفر التوصيل.</p>
                    </button>
                    <button
                        type="button"
                        :class="[
                            'rounded-2xl border-2 p-4 text-right transition',
                            form.delivery_provider === 'PLATFORM'
                                ? 'border-sky-500 bg-sky-50 text-sky-900'
                                : 'border-stone-200 bg-stone-50 text-stone-600',
                        ]"
                        @click="form.delivery_provider = 'PLATFORM'"
                    >
                        <p class="text-sm font-black">توصيل من الموقع</p>
                        <p class="mt-1 text-[11px] opacity-80">المنصة تتحكم في السعر والمطعم لا يعدّله.</p>
                    </button>
                    <button
                        type="button"
                        :class="[
                            'rounded-2xl border-2 p-4 text-right transition',
                            form.delivery_provider === 'PICKUP'
                                ? 'border-emerald-500 bg-emerald-50 text-emerald-900'
                                : 'border-stone-200 bg-stone-50 text-stone-600',
                        ]"
                        @click="form.delivery_provider = 'PICKUP'"
                    >
                        <p class="text-sm font-black">استلام من المطعم</p>
                        <p class="mt-1 text-[11px] opacity-80">العميل يستلم بنفسه بدون رسوم توصيل.</p>
                    </button>
                </div>
                <div v-if="form.delivery_provider !== 'PICKUP'" class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm text-stone-400">رسوم التوصيل (ج.م)</label>
                        <input
                            v-model.number="form.delivery_fee"
                            type="number"
                            step="0.5"
                            class="w-full rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-stone-900 transition-colors focus:border-orange-500 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-stone-400">سعر فتح العداد (ج.م)</label>
                        <input
                            v-model.number="form.delivery_base_fee"
                            type="number"
                            step="0.5"
                            class="w-full rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-stone-900 transition-colors focus:border-orange-500 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-stone-400">سعر الكيلو (ج.م)</label>
                        <input
                            v-model.number="form.delivery_fee_per_km"
                            type="number"
                            step="0.5"
                            class="w-full rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-stone-900 transition-colors focus:border-orange-500 focus:outline-none"
                        />
                    </div>
                </div>
                <p
                    v-else
                    class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-800"
                >
                    الاستلام من المطعم فقط — مفيش رسوم توصيل.
                </p>
            </div>

            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-xs">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-stone-900">
                    <Clock class="h-5 w-5 text-indigo-400" />
                    أوقات العمل والطلب
                </h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm text-stone-400">وقت الافتتاح</label>
                        <input
                            v-model="form.opening_time"
                            type="time"
                            class="w-full rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-stone-900 transition-colors focus:border-orange-500 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-stone-400">وقت الإغلاق</label>
                        <input
                            v-model="form.closing_time"
                            type="time"
                            class="w-full rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-stone-900 transition-colors focus:border-orange-500 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-stone-400">وقت التوصيل (دقيقة)</label>
                        <input
                            v-model.number="form.estimated_delivery_time"
                            type="number"
                            class="w-full rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-stone-900 transition-colors focus:border-orange-500 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-stone-400">الحد الأدنى للطلب (ج.م)</label>
                        <input
                            v-model.number="form.minimum_order_amount"
                            type="number"
                            step="0.01"
                            class="w-full rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-stone-900 transition-colors focus:border-orange-500 focus:outline-none"
                        />
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-xs">
                <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-stone-900">
                    <DollarSign class="h-5 w-5 text-amber-400" />
                    الإعدادات المالية
                </h2>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm text-stone-400">نوع العمولة</label>
                        <select
                            v-model="form.commission_type"
                            class="w-full rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-stone-900 transition-colors focus:border-orange-500 focus:outline-none"
                        >
                            <option value="PERCENTAGE">نسبة</option>
                            <option value="FIXED">ثابت</option>
                            <option value="SUBSCRIPTION">اشتراك</option>
                            <option value="HYBRID">هجين</option>
                            <option value="NONE">بدون</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-stone-400">نسبة/قيمة العمولة</label>
                        <input
                            v-model.number="form.commission_percentage"
                            type="number"
                            step="0.1"
                            min="0"
                            class="w-full rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-stone-900 transition-colors focus:border-orange-500 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-stone-400">اشتراك شهري (ج.م)</label>
                        <input
                            v-model.number="form.monthly_subscription_fee"
                            type="number"
                            step="0.01"
                            min="0"
                            class="w-full rounded-lg border border-stone-200 bg-white px-4 py-2.5 text-stone-900 transition-colors focus:border-orange-500 focus:outline-none"
                        />
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <Link
                    :href="`/admin/restaurants/${restaurant.id}`"
                    class="rounded-xl border border-stone-200 bg-white px-6 py-2.5 font-bold text-stone-600 hover:bg-stone-50"
                >
                    إلغاء
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex items-center gap-2 rounded-xl bg-orange-500 px-6 py-2.5 font-bold text-white hover:bg-orange-400 disabled:opacity-50"
                >
                    <Save class="h-4 w-4" />
                    {{ form.processing ? 'جارٍ الحفظ...' : 'حفظ التعديلات' }}
                </button>
            </div>
        </form>
    </div>
</template>
