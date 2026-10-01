<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import type { Customer, Order } from '../../Types';
import {
    ShoppingBag,
    Clock,
    ArrowRight,
    GraduationCap,
    CheckCircle2,
    AlertCircle,
    Store,
    Bike,
} from '@lucide/vue';

defineProps<{
    customer: Customer;
    recent_orders?: Order[];
    active_order: Order | null;
}>();

const statusMap: Record<string, { label: string; bg: string; text: string }> = {
    PENDING: { label: 'قيد الانتظار', bg: 'bg-amber-100 dark:bg-amber-950', text: 'text-amber-800 dark:text-amber-300' },
    CONFIRMED: { label: 'تم تأكيد الطلب', bg: 'bg-blue-100 dark:bg-blue-950', text: 'text-blue-800 dark:text-blue-300' },
    PREPARING: { label: 'المطعم يجهز الوجبة', bg: 'bg-orange-100 dark:bg-orange-950', text: 'text-orange-800 dark:text-orange-300' },
    READY_FOR_PICKUP: { label: 'جاهز للاستلام', bg: 'bg-purple-100 dark:bg-purple-950', text: 'text-purple-800 dark:text-purple-300' },
    ASSIGNED_TO_DRIVER: { label: 'تم تعيين الطيار', bg: 'bg-indigo-100 dark:bg-indigo-950', text: 'text-indigo-800 dark:text-indigo-300' },
    OUT_FOR_DELIVERY: { label: 'الطيار في الطريق إليك', bg: 'bg-teal-100 dark:bg-teal-950', text: 'text-teal-800 dark:text-teal-300' },
    DELIVERED: { label: 'تم التوصيل بنجاح', bg: 'bg-emerald-100 dark:bg-emerald-950', text: 'text-emerald-800 dark:text-emerald-300' },
    CANCELLED: { label: 'ملغي', bg: 'bg-red-100 dark:bg-red-950', text: 'text-red-800 dark:text-red-300' },
};

const getStatusMeta = (status: string) =>
    statusMap[status] || { label: status, bg: 'bg-stone-100', text: 'text-stone-700' };

const formatOrderDate = (date: string): string => new Date(date).toLocaleDateString('ar-EG');
</script>

<template>
    <div class="px-4 py-4">
        <Head title="لوحة تحكم الطالب والعميل" />

        <div class="space-y-8">
            <section class="relative isolate overflow-hidden rounded-[2rem] bg-orange-600 px-6 py-7 text-white shadow-xl shadow-orange-500/20 sm:px-8">
                <div class="absolute -left-12 -top-16 h-44 w-44 rounded-full bg-amber-300/35 blur-3xl" />
                <div class="absolute bottom-0 right-0 h-28 w-2/3 bg-[radial-gradient(circle_at_bottom_right,rgba(255,255,255,.22),transparent_60%)]" />
                <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-[10px] font-black tracking-[.22em] text-orange-100 uppercase">طلباتك، على مزاجك</p>
                        <h1 class="mt-2 text-2xl font-black sm:text-3xl">ماذا تريد أن تطلب اليوم؟</h1>
                        <p class="mt-2 max-w-lg text-sm text-orange-50/85">اختر مطعمك، حدّد مكانك، وتابع كل خطوة حتى يصل طلبك.</p>
                    </div>
                    <Link
                        href="/restaurants"
                        prefetch
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-white px-4 py-3 text-xs font-black text-orange-700 shadow-lg transition hover:-translate-y-0.5 hover:bg-orange-50"
                    >
                        اكتشف المطاعم <ArrowRight class="h-4 w-4 rotate-180" />
                    </Link>
                </div>
                <div class="relative mt-6 flex items-center gap-3 text-xs text-orange-50/90">
                    <span class="grid h-8 w-8 place-items-center rounded-xl bg-white/15"><ShoppingBag class="h-4 w-4" /></span>
                    <span>
                        {{
                            active_order
                                ? `لديك طلب جاري من ${active_order.restaurant?.name || 'مطعم شريك'}`
                                : 'اطلب الآن من مطاعمك المفضلة في دقائق'
                        }}
                    </span>
                </div>
            </section>

            <div
                v-if="customer.student_status === 'APPROVED'"
                class="p-6 rounded-3xl bg-gradient-to-r from-orange-500/10 via-amber-500/10 to-transparent border border-orange-500/30 flex items-center justify-between gap-4"
            >
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-orange-600 text-white flex items-center justify-center shrink-0 shadow-md">
                        <GraduationCap class="w-7 h-7" />
                    </div>
                    <div>
                        <h3 class="font-extrabold text-stone-900 dark:text-white text-base flex items-center gap-2">
                            <span>كارنيه الطالب مفعل — تمتع بخصومات حصرية</span>
                            <CheckCircle2 class="w-4 h-4 text-emerald-500" />
                        </h3>
                        <p class="text-xs text-stone-600 dark:text-stone-400 mt-0.5">
                            {{ customer.university_name || 'جامعة برج العرب التكنولوجية' }} — خصومات 10% إلى 20% تطبق على كل سلة طلبات تلقائياً.
                        </p>
                    </div>
                </div>
                <Link
                    href="/restaurants"
                    class="hidden sm:inline-flex px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold shadow transition shrink-0"
                >
                    اطلب الآن بخصمك
                </Link>
            </div>
            <div
                v-else
                class="p-6 rounded-3xl bg-amber-50 dark:bg-stone-800/60 border border-amber-300 dark:border-stone-700 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
            >
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0">
                        <AlertCircle class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="font-extrabold text-stone-900 dark:text-white text-sm">
                            هل أنت طالب في برج العرب التكنولوجية أو الجامعة اليابانية؟
                        </h3>
                        <p class="text-xs text-stone-600 dark:text-stone-400 mt-0.5">
                            ارفع صورة كارنيه الجامعة لتحصل فوراً على خصم الطلاب في جميع مطاعم المنصة.
                        </p>
                    </div>
                </div>
                <Link
                    href="/customer/profile#student-id"
                    class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow transition shrink-0"
                >
                    توثيق الكارنيه الآن
                </Link>
            </div>

            <div
                v-if="active_order"
                class="p-6 rounded-3xl bg-white dark:bg-stone-900 border-2 border-orange-500 shadow-xl space-y-4 animate-fade-in"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-orange-100 dark:bg-orange-950 text-orange-600 flex items-center justify-center font-bold">
                            <Bike class="w-5 h-5 animate-pulse" />
                        </div>
                        <div>
                            <span class="text-xs font-bold text-orange-600 uppercase tracking-wider block">طلبك قيد التوصيل الآن</span>
                            <h3 class="text-base font-black text-stone-900 dark:text-white">
                                {{ active_order.restaurant?.name }}
                            </h3>
                        </div>
                    </div>
                    <span
                        class="px-2.5 py-0.5 rounded-full text-xs font-bold"
                        :class="[getStatusMeta(active_order.status).bg, getStatusMeta(active_order.status).text]"
                    >
                        {{ getStatusMeta(active_order.status).label }}
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3 bg-stone-50 dark:bg-stone-800/50 rounded-2xl text-xs">
                    <div>
                        <span class="text-stone-400 block text-[10px]">رقم الطلب</span>
                        <span class="font-mono font-bold text-stone-800 dark:text-stone-200">{{ active_order.order_number }}</span>
                    </div>
                    <div>
                        <span class="text-stone-400 block text-[10px]">الإجمالي</span>
                        <span class="font-bold text-orange-600">{{ active_order.total_amount }} ج.م</span>
                    </div>
                    <div>
                        <span class="text-stone-400 block text-[10px]">طريقة الدفع</span>
                        <span class="font-bold text-stone-700 dark:text-stone-300">كاش عند الاستلام</span>
                    </div>
                    <div>
                        <span class="text-stone-400 block text-[10px]">الكابتن</span>
                        <span class="font-bold text-stone-700 dark:text-stone-300">
                            {{ active_order.deliveryDriver?.name || 'جارٍ التعيين...' }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <Link
                        :href="`/customer/orders/${active_order.order_number}`"
                        class="px-5 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs shadow-md transition flex items-center gap-1.5"
                    >
                        <span>تتبع الطلب بالخريطة</span>
                        <ArrowRight class="w-3.5 h-3.5 rotate-180" />
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <Link
                    href="/restaurants"
                    class="p-5 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 hover:border-orange-500 transition group flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-orange-100 dark:bg-orange-950 text-orange-600 flex items-center justify-center">
                            <Store class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="font-bold text-xs text-stone-900 dark:text-white">تصفح المطاعم</h4>
                            <p class="text-[10px] text-stone-400">اختر وجبتك المفضلة</p>
                        </div>
                    </div>
                    <ArrowRight class="w-4 h-4 rotate-180 text-stone-400 group-hover:text-orange-600 transition" />
                </Link>

                <Link
                    href="/cart"
                    class="p-5 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 hover:border-orange-500 transition group flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950 text-amber-600 flex items-center justify-center">
                            <ShoppingBag class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="font-bold text-xs text-stone-900 dark:text-white">سلة التسوق</h4>
                            <p class="text-[10px] text-stone-400">أكمل طلبك المعلق</p>
                        </div>
                    </div>
                    <ArrowRight class="w-4 h-4 rotate-180 text-stone-400 group-hover:text-orange-600 transition" />
                </Link>

                <Link
                    href="/customer/orders"
                    class="p-5 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 hover:border-orange-500 transition group flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-950 text-blue-600 flex items-center justify-center">
                            <Clock class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="font-bold text-xs text-stone-900 dark:text-white">سجل الطلبات</h4>
                            <p class="text-[10px] text-stone-400">إعادة طلب بنقرة واحدة</p>
                        </div>
                    </div>
                    <ArrowRight class="w-4 h-4 rotate-180 text-stone-400 group-hover:text-orange-600 transition" />
                </Link>
            </div>

            <div class="bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-6 shadow-xs">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-2">
                        <Clock class="w-4 h-4 text-orange-500" />
                        <span>آخر الطلبات</span>
                    </h3>
                    <Link href="/customer/orders" class="text-xs font-bold text-orange-600 hover:underline">
                        عرض السجل بالكامل
                    </Link>
                </div>

                <div v-if="!recent_orders || recent_orders.length === 0" class="text-center py-10">
                    <ShoppingBag class="w-12 h-12 text-stone-300 dark:text-stone-600 mx-auto mb-2" />
                    <p class="text-xs text-stone-500 dark:text-stone-400">لم تقم بأي طلبات بعد.</p>
                    <Link href="/restaurants" class="inline-block mt-3 text-xs font-bold text-orange-600 hover:underline">
                        اطلب فطارك الآن
                    </Link>
                </div>
                <div v-else class="divide-y divide-stone-100 dark:divide-stone-800">
                    <div
                        v-for="order in recent_orders"
                        :key="order.id"
                        class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                    >
                        <div>
                            <div class="flex items-center gap-3">
                                <h4 class="font-bold text-sm text-stone-900 dark:text-white">
                                    {{ order.restaurant?.name || 'مطعم شريك' }}
                                </h4>
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-xs font-bold"
                                    :class="[getStatusMeta(order.status).bg, getStatusMeta(order.status).text]"
                                >
                                    {{ getStatusMeta(order.status).label }}
                                </span>
                            </div>
                            <p class="text-xs text-stone-400 mt-1 font-mono">
                                {{ order.order_number }} • {{ formatOrderDate(order.created_at) }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-4">
                            <span class="font-black text-sm text-stone-900 dark:text-white">
                                {{ order.total_amount }} ج.م
                            </span>
                            <Link
                                :href="`/customer/orders/${order.order_number}`"
                                class="px-3.5 py-1.5 rounded-xl border border-stone-200 dark:border-stone-700 text-xs font-bold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-800 transition"
                            >
                                التفاصيل
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
