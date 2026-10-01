<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Save, Globe, Phone, Settings2 } from '@lucide/vue';

const props = defineProps<{
    settings: Record<string, string>;
}>();

const form = useForm({
    platform_name_ar: props.settings.platform_name_ar ?? '',
    platform_name_en: props.settings.platform_name_en ?? '',
    hero_title: props.settings.hero_title ?? '',
    hero_subtitle: props.settings.hero_subtitle ?? '',
    city_badge: props.settings.city_badge ?? '',
    student_banner_title: props.settings.student_banner_title ?? '',
    contact_phone: props.settings.contact_phone ?? '',
    contact_whatsapp: props.settings.contact_whatsapp ?? '',
    contact_email: props.settings.contact_email ?? '',
    footer_description: props.settings.footer_description ?? '',
});

const submit = (): void => {
    form.put('/admin/cms');
};
</script>

<template>
    <Head title="إدارة محتوى الصفحة الرئيسية" />

    <div class="max-w-3xl space-y-6" dir="rtl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-stone-900">إدارة محتوى الصفحة الرئيسية</h1>
                <p class="text-stone-400 text-sm mt-1">تحكم في نصوص وبيانات Landing Page</p>
            </div>
            <span
                v-if="form.wasSuccessful"
                class="px-3 py-1 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded-full text-sm"
            >
                ✓ تم الحفظ
            </span>
        </div>

        <p v-if="Object.keys(form.errors).length" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
            لم يتم حفظ المحتوى. راجع الحقول وحاول مرة أخرى.
        </p>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 flex items-center gap-2">
                    <Globe class="w-5 h-5 text-orange-400" />
                    هوية المنصة
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">اسم المنصة (عربي)</label>
                        <input
                            id="platform_name_ar"
                            v-model="form.platform_name_ar"
                            type="text"
                            placeholder="وصلة"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                        />
                    </div>
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">اسم المنصة (إنجليزي)</label>
                        <input
                            id="platform_name_en"
                            v-model="form.platform_name_en"
                            type="text"
                            placeholder="Wasla"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                        />
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-stone-400 mb-1">شارة المدينة</label>
                    <input
                        id="city_badge"
                        v-model="form.city_badge"
                        type="text"
                        placeholder="برج العرب والإسكندرية"
                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                    />
                </div>
            </div>

            <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 flex items-center gap-2">
                    <Settings2 class="w-5 h-5 text-indigo-400" />
                    قسم Hero
                </h2>
                <div>
                    <label class="block text-sm text-stone-400 mb-1">عنوان Hero الرئيسي</label>
                    <input
                        id="hero_title"
                        v-model="form.hero_title"
                        type="text"
                        placeholder="أسرع وألذ فطار وغدا وعشا في برج العرب"
                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                    />
                </div>
                <div>
                    <label class="block text-sm text-stone-400 mb-1">نص Hero الفرعي</label>
                    <textarea
                        id="hero_subtitle"
                        v-model="form.hero_subtitle"
                        rows="3"
                        placeholder="اطلب من مطاعم برج العرب المفضلة..."
                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors resize-none text-sm"
                    />
                </div>
                <div>
                    <label class="block text-sm text-stone-400 mb-1">شعار طلاب الجامعة</label>
                    <input
                        id="student_banner_title"
                        v-model="form.student_banner_title"
                        type="text"
                        placeholder="خصومات خاصة لطلاب جامعة برج العرب التكنولوجية"
                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                    />
                </div>
            </div>

            <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 flex items-center gap-2">
                    <Phone class="w-5 h-5 text-emerald-400" />
                    معلومات الاتصال
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">رقم الهاتف</label>
                        <input
                            id="contact_phone"
                            v-model="form.contact_phone"
                            type="text"
                            placeholder="01000000000"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                        />
                    </div>
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">واتساب</label>
                        <input
                            id="contact_whatsapp"
                            v-model="form.contact_whatsapp"
                            type="text"
                            placeholder="201000000000"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                        />
                    </div>
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">البريد الإلكتروني</label>
                        <input
                            id="contact_email"
                            v-model="form.contact_email"
                            type="text"
                            placeholder="support@fatrna-shokran.com"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm"
                        />
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-stone-400 mb-1">وصف الفوتر</label>
                    <textarea
                        id="footer_description"
                        v-model="form.footer_description"
                        rows="3"
                        placeholder="منصة وصلة — توصيل الطعام الأسرع في برج العرب"
                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors resize-none text-sm"
                    />
                </div>
            </div>

            <div class="flex justify-end">
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
