<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Save, Settings, Globe, Mail, CreditCard, Clock } from '@lucide/vue';

const props = defineProps<{
    settings: Record<string, string>;
}>();

const form = useForm({
    app_name: props.settings.app_name ?? 'وصلة',
    app_tagline: props.settings.app_tagline ?? '',
    support_email: props.settings.support_email ?? '',
    support_phone: props.settings.support_phone ?? '',
    default_commission_rate: props.settings.default_commission_rate ?? '15',
    default_tax_rate: props.settings.default_tax_rate ?? '14',
    default_delivery_fee: props.settings.default_delivery_fee ?? '10.00',
    order_auto_cancel_minutes: props.settings.order_auto_cancel_minutes ?? '30',
    maintenance_mode: props.settings.maintenance_mode ?? '0',
    allow_registrations: props.settings.allow_registrations ?? '1',
});

const submit = (): void => {
    form.put('/admin/settings');
};

const toggle = (field: 'maintenance_mode' | 'allow_registrations'): void => {
    form[field] = form[field] === '1' ? '0' : '1';
};
</script>

<template>
    <Head title="إعدادات النظام" />

    <div class="max-w-3xl space-y-6" dir="rtl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 flex items-center gap-2">
                    <Settings class="w-6 h-6 text-orange-400" />
                    إعدادات النظام
                </h1>
                <p class="text-stone-400 text-sm mt-1">الإعدادات العامة للمنصة</p>
            </div>
            <span
                v-if="form.wasSuccessful"
                class="px-3 py-1 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded-full text-sm"
            >
                ✓ تم الحفظ
            </span>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 flex items-center gap-2">
                    <Globe class="w-5 h-5 text-orange-400" />
                    الإعدادات العامة
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="app_name" class="block text-sm text-stone-400 mb-1">اسم التطبيق</label>
                        <input
                            id="app_name"
                            v-model="form.app_name"
                            type="text"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                        />
                    </div>
                    <div>
                        <label for="app_tagline" class="block text-sm text-stone-400 mb-1">الشعار (Tagline)</label>
                        <input
                            id="app_tagline"
                            v-model="form.app_tagline"
                            type="text"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                        />
                    </div>
                </div>
            </div>

            <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 flex items-center gap-2">
                    <Mail class="w-5 h-5 text-indigo-400" />
                    معلومات الدعم
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="support_email" class="block text-sm text-stone-400 mb-1">بريد الدعم</label>
                        <input
                            id="support_email"
                            v-model="form.support_email"
                            type="email"
                            placeholder="support@fatrna-shokran.com"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                        />
                    </div>
                    <div>
                        <label for="support_phone" class="block text-sm text-stone-400 mb-1">هاتف الدعم</label>
                        <input
                            id="support_phone"
                            v-model="form.support_phone"
                            type="text"
                            placeholder="01000000000"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                        />
                    </div>
                </div>
            </div>

            <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 flex items-center gap-2">
                    <CreditCard class="w-5 h-5 text-amber-400" />
                    الإعدادات المالية الافتراضية
                </h2>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="default_commission_rate" class="block text-sm text-stone-400 mb-1">نسبة العمولة الافتراضية (%)</label>
                        <input
                            id="default_commission_rate"
                            v-model="form.default_commission_rate"
                            type="number"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                        />
                    </div>
                    <div>
                        <label for="default_tax_rate" class="block text-sm text-stone-400 mb-1">نسبة الضريبة الافتراضية (%)</label>
                        <input
                            id="default_tax_rate"
                            v-model="form.default_tax_rate"
                            type="number"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                        />
                    </div>
                    <div>
                        <label for="default_delivery_fee" class="block text-sm text-stone-400 mb-1">رسوم التوصيل الافتراضية (ج.م)</label>
                        <input
                            id="default_delivery_fee"
                            v-model="form.default_delivery_fee"
                            type="number"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                        />
                    </div>
                </div>
            </div>

            <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 flex items-center gap-2">
                    <Clock class="w-5 h-5 text-purple-400" />
                    إعدادات الطلبات
                </h2>
                <div>
                    <label for="order_auto_cancel_minutes" class="block text-sm text-stone-400 mb-1">مدة إلغاء الطلب تلقائياً (دقيقة)</label>
                    <input
                        id="order_auto_cancel_minutes"
                        v-model="form.order_auto_cancel_minutes"
                        type="number"
                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                    />
                </div>
            </div>

            <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 mb-4">خيارات النظام</h2>
                <div class="flex items-start justify-between py-3 border-b border-stone-200 last:border-0">
                    <div>
                        <label for="maintenance_mode" class="text-stone-900 text-sm font-medium cursor-pointer">وضع الصيانة</label>
                        <p class="text-stone-400 text-xs mt-0.5">تعطيل الموقع مؤقتاً للصيانة</p>
                    </div>
                    <button
                        id="maintenance_mode"
                        type="button"
                        :class="[
                            'relative w-11 h-6 rounded-full transition-colors',
                            form.maintenance_mode === '1' ? 'bg-orange-500' : 'bg-stone-200',
                        ]"
                        @click="toggle('maintenance_mode')"
                    >
                        <span
                            :class="[
                                'absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform',
                                form.maintenance_mode === '1' ? 'translate-x-5' : 'translate-x-0.5',
                            ]"
                        />
                    </button>
                </div>
                <div class="flex items-start justify-between py-3 border-b border-stone-200 last:border-0">
                    <div>
                        <label for="allow_registrations" class="text-stone-900 text-sm font-medium cursor-pointer">السماح بالتسجيل</label>
                        <p class="text-stone-400 text-xs mt-0.5">السماح للعملاء الجدد بإنشاء حسابات</p>
                    </div>
                    <button
                        id="allow_registrations"
                        type="button"
                        :class="[
                            'relative w-11 h-6 rounded-full transition-colors',
                            form.allow_registrations === '1' ? 'bg-orange-500' : 'bg-stone-200',
                        ]"
                        @click="toggle('allow_registrations')"
                    >
                        <span
                            :class="[
                                'absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform',
                                form.allow_registrations === '1' ? 'translate-x-5' : 'translate-x-0.5',
                            ]"
                        />
                    </button>
                </div>
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex items-center gap-2 px-6 py-2.5 bg-orange-500 hover:bg-orange-400 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                >
                    <Save class="w-4 h-4" />
                    {{ form.processing ? 'جاري الحفظ...' : 'حفظ الإعدادات' }}
                </button>
            </div>
        </form>
    </div>
</template>
