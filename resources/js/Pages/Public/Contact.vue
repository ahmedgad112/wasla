<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import { MapPin, Phone, Clock, Send, CheckCircle2, MessageSquare } from '@lucide/vue';
import type { SharedInertiaProps } from '../../Types';

const page = usePage<SharedInertiaProps>();
const site = computed(() => page.props.site);
const contactPhone = computed(() => site.value?.contact_phone || site.value?.support_phone || '');
const contactEmail = computed(() => site.value?.contact_email || site.value?.support_email || '');

const submitted = ref(false);
const formData = ref({
    name: '',
    phone: '',
    email: '',
    subject: '',
    message: '',
});

const handleSubmit = (): void => {
    submitted.value = true;
};
</script>

<template>
        <Head title="تواصل معنا والدعم الفني" />

        <div class="bg-gradient-to-b from-orange-50 to-transparent dark:from-stone-900 py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div class="inline-flex items-center gap-2 text-xs font-bold uppercase text-orange-600 dark:text-orange-400 tracking-wider mb-2">
                    <MessageSquare class="w-4 h-4" />
                    <span>فريق الدعم الفني وخدمة العملاء</span>
                </div>
                <h1 class="text-xl font-black text-stone-900 dark:text-white">
                    نحن هنا لمساعدتك في أي وقت
                </h1>
                <p class="text-sm text-stone-600 dark:text-stone-400 max-w-xl mx-auto mt-2">
                    لديك استفسار حول طلبك، تريد انضمام مطعمك، أو تواجه مشكلة في توثيق كارنيه الطالب؟ تواصل معنا مباشرة.
                </p>
            </div>
        </div>

        <div class="px-4 py-4">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                <div class="space-y-6">
                    <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-orange-100 dark:bg-orange-950/80 text-orange-600 flex items-center justify-center shrink-0">
                            <MapPin class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-stone-900 dark:text-white mb-1">المقر الرئيسي</h3>
                            <p class="text-xs text-stone-600 dark:text-stone-400 leading-relaxed">
                                {{ site?.office_address || 'مدينة برج العرب الجديدة — بجوار مجمع الجامعات، الإسكندرية' }}
                            </p>
                        </div>
                    </div>

                    <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 flex items-center justify-center shrink-0">
                            <Phone class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-stone-900 dark:text-white mb-1">الهاتف وواتساب</h3>
                            <p class="text-xs font-mono text-stone-600 dark:text-stone-400 leading-relaxed" dir="ltr">
                                <a v-if="contactPhone" :href="`tel:${contactPhone}`" class="block">{{ contactPhone }}</a>
                                <a
                                    v-if="site?.contact_whatsapp"
                                    :href="`https://wa.me/${site.contact_whatsapp.replace(/\D/g, '')}`"
                                    target="_blank"
                                    rel="noopener"
                                    class="block"
                                >
                                    واتساب {{ site.contact_whatsapp }}
                                </a>
                                <span v-if="contactEmail" class="block">{{ contactEmail }}</span>
                            </p>
                        </div>
                    </div>

                    <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs flex items-start gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-100 dark:bg-amber-950/80 text-amber-600 flex items-center justify-center shrink-0">
                            <Clock class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-stone-900 dark:text-white mb-1">ساعات العمل والتوصيل</h3>
                            <p class="text-xs text-stone-600 dark:text-stone-400 leading-relaxed">
                                {{ site?.working_hours || 'يومياً من 6:00 صباحاً حتى 2:00 بعد منتصف الليل' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2 p-8 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                    <div v-if="submitted" class="text-center py-12">
                        <div class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-4">
                            <CheckCircle2 class="w-8 h-8" />
                        </div>
                        <h3 class="text-xl font-black text-stone-900 dark:text-white mb-2">
                            تم استلام رسالتك بنجاح!
                        </h3>
                        <p class="text-sm text-stone-600 dark:text-stone-400 max-w-md mx-auto">
                            شكراً لتواصلك معنا، سيقوم فريق خدمة عملاء وصلة بمراجعة رسالتك والرد عليك عبر الهاتف أو الواتساب في أقرب وقت.
                        </p>
                    </div>
                    <form v-else class="space-y-4" @submit.prevent="handleSubmit">
                        <h2 class="text-xl font-black text-stone-900 dark:text-white mb-4">
                            أرسل استفسارك أو طلبك
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1">
                                    الاسم بالكامل
                                </label>
                                <input
                                    v-model="formData.name"
                                    type="text"
                                    required
                                    class="w-full p-3 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500"
                                    placeholder="أحمد محمد"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1">
                                    رقم الهاتف أو الواتساب
                                </label>
                                <input
                                    v-model="formData.phone"
                                    type="tel"
                                    required
                                    class="w-full p-3 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500"
                                    placeholder="010xxxxxxxx"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1">
                                موضوع الرسالة
                            </label>
                            <input
                                v-model="formData.subject"
                                type="text"
                                required
                                class="w-full p-3 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500"
                                placeholder="استفسار، طلب انضمام مطعم، مشكلة في توثيق الكارنيه..."
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1">
                                نص الرسالة
                            </label>
                            <textarea
                                v-model="formData.message"
                                required
                                rows="4"
                                class="w-full p-3 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500"
                                placeholder="اكتب تفاصيل استفسارك هنا..."
                            />
                        </div>

                        <button
                            type="submit"
                            class="w-full py-3.5 px-6 rounded-xl bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 text-white font-bold text-sm shadow-md transition flex items-center justify-center gap-2"
                        >
                            <Send class="w-4 h-4" />
                            <span>إرسال الرسالة الآن</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
</template>
