<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save, Settings, Globe, Mail, CreditCard, Clock, LayoutTemplate, SlidersHorizontal } from '@lucide/vue';

const props = defineProps<{
    settings: Record<string, string>;
}>();

const sections = [
    { id: 'brand', label: 'الهوية', icon: Globe },
    { id: 'home', label: 'الرئيسية', icon: LayoutTemplate },
    { id: 'contact', label: 'التواصل', icon: Mail },
    { id: 'money', label: 'المال والطلبات', icon: CreditCard },
    { id: 'control', label: 'التحكم', icon: SlidersHorizontal },
] as const;

type SectionId = (typeof sections)[number]['id'];
type ToggleField =
    | 'maintenance_mode'
    | 'allow_registrations'
    | 'show_offers_section'
    | 'show_restaurants_section'
    | 'show_leaderboard_section'
    | 'show_stats_section';

const activeSection = ref<SectionId>('home');

const value = (key: string, fallback = ''): string => props.settings[key] ?? fallback;

const form = useForm({
    app_name: value('app_name', 'وصلة'),
    platform_name_en: value('platform_name_en', 'Wasla'),
    app_tagline: value('app_tagline'),
    hero_title: value('hero_title', 'هتطلب إيه النهاردة؟'),
    hero_subtitle: value('hero_subtitle'),
    home_headline: value('hero_title', 'هتطلب إيه النهاردة؟'),
    search_placeholder: value('search_placeholder', 'ابحث عن مطعم أو وجبة...'),
    city_badge: value('city_badge', 'جامعة برج العرب'),
    offers_section_title: value('offers_section_title', 'عروض النهاردة'),
    restaurants_section_title: value('restaurants_section_title', 'مطاعم قريبة منك'),
    leaderboard_section_title: value('leaderboard_section_title', 'الأكثر طلباً'),
    student_banner_title: value('student_banner_title'),
    footer_description: value('footer_description'),
    meta_title: value('meta_title'),
    meta_description: value('meta_description'),
    support_email: value('support_email'),
    support_phone: value('support_phone'),
    contact_phone: value('contact_phone'),
    contact_whatsapp: value('contact_whatsapp'),
    contact_email: value('contact_email'),
    office_address: value('office_address'),
    working_hours: value('working_hours'),
    facebook_url: value('facebook_url'),
    instagram_url: value('instagram_url'),
    tiktok_url: value('tiktok_url'),
    default_commission_rate: value('default_commission_rate', '15'),
    default_tax_rate: value('default_tax_rate', '14'),
    default_delivery_fee: value('default_delivery_fee', '10.00'),
    minimum_order_amount: value('minimum_order_amount', '0'),
    order_auto_cancel_minutes: value('order_auto_cancel_minutes', '30'),
    maintenance_mode: value('maintenance_mode', '0'),
    allow_registrations: value('allow_registrations', '1'),
    show_offers_section: value('show_offers_section', '1'),
    show_restaurants_section: value('show_restaurants_section', '1'),
    show_leaderboard_section: value('show_leaderboard_section', '1'),
    show_stats_section: value('show_stats_section', '1'),
});

const inputClass = 'w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors text-sm';

const submit = (): void => {
    form.home_headline = form.hero_title;
    form.put('/admin/settings', {
        preserveScroll: true,
    });
};

const toggle = (field: ToggleField): void => {
    form[field] = form[field] === '1' ? '0' : '1';
};
</script>

<template>
    <Head title="إعدادات النظام" />

    <div class="max-w-4xl space-y-6" dir="rtl">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 flex items-center gap-2">
                    <Settings class="w-6 h-6 text-orange-400" />
                    إعدادات النظام
                </h1>
                <p class="text-stone-400 text-sm mt-1">تحكم في اسم الموقع، الصفحة الرئيسية، التواصل، الأسعار، وأقسام الموقع</p>
            </div>
            <span
                v-if="form.wasSuccessful"
                class="px-3 py-1 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded-full text-sm"
            >
                ✓ تم الحفظ
            </span>
        </div>

        <p v-if="Object.keys(form.errors).length" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">
            لم يتم حفظ الإعدادات. راجع الحقول وحاول مرة أخرى.
        </p>

        <div class="flex gap-2 overflow-x-auto pb-1">
            <button
                v-for="section in sections"
                :key="section.id"
                type="button"
                class="shrink-0 inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold transition"
                :class="activeSection === section.id ? 'bg-stone-900 text-white' : 'bg-white text-stone-600 ring-1 ring-stone-200'"
                @click="activeSection = section.id"
            >
                <component :is="section.icon" class="h-3.5 w-3.5" />
                {{ section.label }}
            </button>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <div v-show="activeSection === 'brand'" class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900">هوية الموقع</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="app_name" class="block text-sm text-stone-500 mb-1">اسم الموقع</label>
                        <input id="app_name" v-model="form.app_name" type="text" :class="inputClass" />
                        <p v-if="form.errors.app_name" class="mt-1 text-xs text-red-600">{{ form.errors.app_name }}</p>
                    </div>
                    <div>
                        <label for="platform_name_en" class="block text-sm text-stone-500 mb-1">الاسم بالإنجليزي</label>
                        <input id="platform_name_en" v-model="form.platform_name_en" type="text" :class="inputClass" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="app_tagline" class="block text-sm text-stone-500 mb-1">الشعار</label>
                        <input id="app_tagline" v-model="form.app_tagline" type="text" :class="inputClass" />
                    </div>
                    <div>
                        <label for="meta_title" class="block text-sm text-stone-500 mb-1">عنوان المتصفح</label>
                        <input id="meta_title" v-model="form.meta_title" type="text" :class="inputClass" />
                    </div>
                    <div>
                        <label for="meta_description" class="block text-sm text-stone-500 mb-1">وصف الموقع</label>
                        <input id="meta_description" v-model="form.meta_description" type="text" :class="inputClass" />
                    </div>
                </div>
            </div>

            <div v-show="activeSection === 'home'" class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900">نصوص الصفحة الرئيسية</h2>
                <p class="text-xs text-stone-400">كل حقل هنا هو النص الظاهر للعميل. بعد الحفظ حدّث الصفحة الرئيسية.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="city_badge" class="block text-sm text-stone-500 mb-1">المكان في أعلى الصفحة</label>
                        <input id="city_badge" v-model="form.city_badge" type="text" :class="inputClass" />
                    </div>
                    <div>
                        <label for="hero_title" class="block text-sm text-stone-500 mb-1">العنوان الكبير</label>
                        <input id="hero_title" v-model="form.hero_title" type="text" :class="inputClass" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="hero_subtitle" class="block text-sm text-stone-500 mb-1">السطر تحت العنوان</label>
                        <textarea id="hero_subtitle" v-model="form.hero_subtitle" rows="2" :class="inputClass" />
                    </div>
                    <div>
                        <label for="student_banner_title" class="block text-sm text-stone-500 mb-1">سطر خصم الطلاب</label>
                        <input id="student_banner_title" v-model="form.student_banner_title" type="text" :class="inputClass" />
                    </div>
                    <div>
                        <label for="search_placeholder" class="block text-sm text-stone-500 mb-1">نص صندوق البحث</label>
                        <input id="search_placeholder" v-model="form.search_placeholder" type="text" :class="inputClass" />
                    </div>
                    <div>
                        <label for="offers_section_title" class="block text-sm text-stone-500 mb-1">عنوان قسم العروض</label>
                        <input id="offers_section_title" v-model="form.offers_section_title" type="text" :class="inputClass" />
                    </div>
                    <div>
                        <label for="restaurants_section_title" class="block text-sm text-stone-500 mb-1">عنوان قسم المطاعم</label>
                        <input id="restaurants_section_title" v-model="form.restaurants_section_title" type="text" :class="inputClass" />
                    </div>
                    <div>
                        <label for="leaderboard_section_title" class="block text-sm text-stone-500 mb-1">عنوان الأكثر طلباً</label>
                        <input id="leaderboard_section_title" v-model="form.leaderboard_section_title" type="text" :class="inputClass" />
                    </div>
                    <div class="sm:col-span-2">
                        <label for="footer_description" class="block text-sm text-stone-500 mb-1">وصف أسفل الموقع</label>
                        <textarea id="footer_description" v-model="form.footer_description" rows="3" :class="inputClass" />
                    </div>
                </div>
            </div>

            <div v-show="activeSection === 'contact'" class="space-y-6">
                <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                    <h2 class="text-lg font-semibold text-stone-900">بيانات التواصل</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="support_phone" class="block text-sm text-stone-500 mb-1">هاتف الدعم</label>
                            <input id="support_phone" v-model="form.support_phone" type="text" :class="inputClass" />
                        </div>
                        <div>
                            <label for="support_email" class="block text-sm text-stone-500 mb-1">بريد الدعم</label>
                            <input id="support_email" v-model="form.support_email" type="email" :class="inputClass" />
                            <p v-if="form.errors.support_email" class="mt-1 text-xs text-red-600">{{ form.errors.support_email }}</p>
                        </div>
                        <div>
                            <label for="contact_phone" class="block text-sm text-stone-500 mb-1">هاتف صفحة التواصل</label>
                            <input id="contact_phone" v-model="form.contact_phone" type="text" :class="inputClass" />
                        </div>
                        <div>
                            <label for="contact_whatsapp" class="block text-sm text-stone-500 mb-1">واتساب</label>
                            <input id="contact_whatsapp" v-model="form.contact_whatsapp" type="text" placeholder="201000000000" :class="inputClass" />
                        </div>
                        <div>
                            <label for="contact_email" class="block text-sm text-stone-500 mb-1">بريد التواصل</label>
                            <input id="contact_email" v-model="form.contact_email" type="email" :class="inputClass" />
                            <p v-if="form.errors.contact_email" class="mt-1 text-xs text-red-600">{{ form.errors.contact_email }}</p>
                        </div>
                        <div>
                            <label for="working_hours" class="block text-sm text-stone-500 mb-1">ساعات العمل</label>
                            <input id="working_hours" v-model="form.working_hours" type="text" :class="inputClass" />
                        </div>
                        <div class="sm:col-span-2">
                            <label for="office_address" class="block text-sm text-stone-500 mb-1">العنوان</label>
                            <input id="office_address" v-model="form.office_address" type="text" :class="inputClass" />
                        </div>
                    </div>
                </div>
                <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                    <h2 class="text-lg font-semibold text-stone-900">السوشيال</h2>
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label for="facebook_url" class="block text-sm text-stone-500 mb-1">فيسبوك</label>
                            <input id="facebook_url" v-model="form.facebook_url" type="url" placeholder="https://" :class="inputClass" />
                            <p v-if="form.errors.facebook_url" class="mt-1 text-xs text-red-600">اكتب رابطاً كاملاً يبدأ بـ https://</p>
                        </div>
                        <div>
                            <label for="instagram_url" class="block text-sm text-stone-500 mb-1">إنستجرام</label>
                            <input id="instagram_url" v-model="form.instagram_url" type="url" placeholder="https://" :class="inputClass" />
                            <p v-if="form.errors.instagram_url" class="mt-1 text-xs text-red-600">اكتب رابطاً كاملاً يبدأ بـ https://</p>
                        </div>
                        <div>
                            <label for="tiktok_url" class="block text-sm text-stone-500 mb-1">تيك توك</label>
                            <input id="tiktok_url" v-model="form.tiktok_url" type="url" placeholder="https://" :class="inputClass" />
                            <p v-if="form.errors.tiktok_url" class="mt-1 text-xs text-red-600">اكتب رابطاً كاملاً يبدأ بـ https://</p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-show="activeSection === 'money'" class="space-y-6">
                <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                    <h2 class="text-lg font-semibold text-stone-900">الإعدادات المالية الافتراضية</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="default_commission_rate" class="block text-sm text-stone-500 mb-1">نسبة العمولة (%)</label>
                            <input id="default_commission_rate" v-model="form.default_commission_rate" type="number" min="0" max="100" step="0.01" :class="inputClass" />
                        </div>
                        <div>
                            <label for="default_tax_rate" class="block text-sm text-stone-500 mb-1">نسبة الضريبة (%)</label>
                            <input id="default_tax_rate" v-model="form.default_tax_rate" type="number" min="0" max="100" step="0.01" :class="inputClass" />
                        </div>
                        <div>
                            <label for="default_delivery_fee" class="block text-sm text-stone-500 mb-1">رسوم التوصيل (ج.م)</label>
                            <input id="default_delivery_fee" v-model="form.default_delivery_fee" type="number" min="0" step="0.01" :class="inputClass" />
                        </div>
                        <div>
                            <label for="minimum_order_amount" class="block text-sm text-stone-500 mb-1">الحد الأدنى للطلب (ج.م)</label>
                            <input id="minimum_order_amount" v-model="form.minimum_order_amount" type="number" min="0" step="0.01" :class="inputClass" />
                        </div>
                    </div>
                </div>
                <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                    <h2 class="text-lg font-semibold text-stone-900 flex items-center gap-2">
                        <Clock class="w-5 h-5 text-purple-400" />
                        الطلبات
                    </h2>
                    <div>
                        <label for="order_auto_cancel_minutes" class="block text-sm text-stone-500 mb-1">إلغاء الطلب تلقائياً بعد (دقيقة)</label>
                        <input id="order_auto_cancel_minutes" v-model="form.order_auto_cancel_minutes" type="number" min="1" max="1440" :class="inputClass" />
                    </div>
                </div>
            </div>

            <div v-show="activeSection === 'control'" class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 mb-4">التحكم في الموقع</h2>
                <div class="flex items-start justify-between gap-4 py-3 border-b border-stone-200">
                    <div>
                        <p class="text-stone-900 text-sm font-medium">وضع الصيانة</p>
                        <p class="text-stone-400 text-xs mt-0.5">إخفاء الموقع عن الزوار مع بقاء لوحة الإدارة تعمل</p>
                    </div>
                    <button type="button" class="relative w-11 h-6 rounded-full transition-colors" :class="form.maintenance_mode === '1' ? 'bg-orange-500' : 'bg-stone-200'" @click="toggle('maintenance_mode')">
                        <span class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform" :class="form.maintenance_mode === '1' ? 'translate-x-5' : 'translate-x-0.5'" />
                    </button>
                </div>
                <div class="flex items-start justify-between gap-4 py-3 border-b border-stone-200">
                    <div>
                        <p class="text-stone-900 text-sm font-medium">السماح بتسجيل العملاء</p>
                        <p class="text-stone-400 text-xs mt-0.5">إظهار صفحة إنشاء حساب جديد</p>
                    </div>
                    <button type="button" class="relative w-11 h-6 rounded-full transition-colors" :class="form.allow_registrations === '1' ? 'bg-orange-500' : 'bg-stone-200'" @click="toggle('allow_registrations')">
                        <span class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform" :class="form.allow_registrations === '1' ? 'translate-x-5' : 'translate-x-0.5'" />
                    </button>
                </div>
                <div class="flex items-start justify-between gap-4 py-3 border-b border-stone-200">
                    <div>
                        <p class="text-stone-900 text-sm font-medium">قسم العروض</p>
                        <p class="text-stone-400 text-xs mt-0.5">إظهار عروض النهاردة في الرئيسية</p>
                    </div>
                    <button type="button" class="relative w-11 h-6 rounded-full transition-colors" :class="form.show_offers_section === '1' ? 'bg-orange-500' : 'bg-stone-200'" @click="toggle('show_offers_section')">
                        <span class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform" :class="form.show_offers_section === '1' ? 'translate-x-5' : 'translate-x-0.5'" />
                    </button>
                </div>
                <div class="flex items-start justify-between gap-4 py-3 border-b border-stone-200">
                    <div>
                        <p class="text-stone-900 text-sm font-medium">قسم المطاعم</p>
                        <p class="text-stone-400 text-xs mt-0.5">إظهار قائمة المطاعم في الرئيسية</p>
                    </div>
                    <button type="button" class="relative w-11 h-6 rounded-full transition-colors" :class="form.show_restaurants_section === '1' ? 'bg-orange-500' : 'bg-stone-200'" @click="toggle('show_restaurants_section')">
                        <span class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform" :class="form.show_restaurants_section === '1' ? 'translate-x-5' : 'translate-x-0.5'" />
                    </button>
                </div>
                <div class="flex items-start justify-between gap-4 py-3 border-b border-stone-200">
                    <div>
                        <p class="text-stone-900 text-sm font-medium">لوحة الأكثر طلباً</p>
                        <p class="text-stone-400 text-xs mt-0.5">إظهار بطاقة المتصدرين في الرئيسية</p>
                    </div>
                    <button type="button" class="relative w-11 h-6 rounded-full transition-colors" :class="form.show_leaderboard_section === '1' ? 'bg-orange-500' : 'bg-stone-200'" @click="toggle('show_leaderboard_section')">
                        <span class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform" :class="form.show_leaderboard_section === '1' ? 'translate-x-5' : 'translate-x-0.5'" />
                    </button>
                </div>
                <div class="flex items-start justify-between gap-4 py-3">
                    <div>
                        <p class="text-stone-900 text-sm font-medium">إحصائيات المنصة</p>
                        <p class="text-stone-400 text-xs mt-0.5">السماح بعرض أرقام المطاعم والطلبات</p>
                    </div>
                    <button type="button" class="relative w-11 h-6 rounded-full transition-colors" :class="form.show_stats_section === '1' ? 'bg-orange-500' : 'bg-stone-200'" @click="toggle('show_stats_section')">
                        <span class="absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform" :class="form.show_stats_section === '1' ? 'translate-x-5' : 'translate-x-0.5'" />
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
