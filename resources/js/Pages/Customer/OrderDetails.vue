<script setup lang="ts">
import { computed, ref, type Component } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import CustomerLiveTrackingMap from '../../Components/CustomerLiveTrackingMap.vue';
import type { Order, OrderStatus } from '../../Types';
import {
    Clock,
    Bike,
    Store,
    MapPin,
    Phone,
    CheckCircle2,
    GraduationCap,
    ArrowRight,
    Sparkles,
    Flame,
    PackageCheck,
    UtensilsCrossed,
    Copy,
    Check,
    Printer,
    ShieldCheck,
    CreditCard,
    AlertTriangle,
    Calendar,
    ChevronDown,
    ChevronUp,
} from '@lucide/vue';

const props = defineProps<{
    order: Order;
}>();

const copied = ref(false);
const showHistory = ref(false);
const driver = computed(() => props.order.delivery_driver || props.order.deliveryDriver);

interface StepInfo {
    key: OrderStatus;
    label: string;
    sublabel: string;
    icon: Component;
    accentColor: string;
    bgLight: string;
    activeText: string;
}

const steps: StepInfo[] = [
    {
        key: 'PENDING',
        label: 'استلام الطلب',
        sublabel: 'تم إرسال طلبك وبانتظار قبول المطعم',
        icon: Clock,
        accentColor: 'from-amber-500 to-orange-500',
        bgLight: 'bg-amber-50 dark:bg-amber-950/30 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-800',
        activeText: 'طلبك قيد المراجعة لدى المطعم حالياً',
    },
    {
        key: 'CONFIRMED',
        label: 'تم التأكيد',
        sublabel: 'المطعم وافق وبدأ جدول التجهيز',
        icon: CheckCircle2,
        accentColor: 'from-blue-500 to-indigo-500',
        bgLight: 'bg-blue-50 dark:bg-blue-950/30 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-800',
        activeText: 'تم تأكيد طلبك بنجاح وجارٍ إسناده للمطبخ',
    },
    {
        key: 'PREPARING',
        label: 'جاري التحضير',
        sublabel: 'الشيف يقوم بطهي وتجهيز وجبتك طازجة',
        icon: Flame,
        accentColor: 'from-orange-500 to-red-500',
        bgLight: 'bg-orange-50 dark:bg-orange-950/30 text-orange-600 dark:text-orange-400 border-orange-200 dark:border-orange-800',
        activeText: 'وجبتك الآن على النار ويتم إعدادها بعناية',
    },
    {
        key: 'READY_FOR_PICKUP',
        label: 'جاهز للاستلام',
        sublabel: 'الوجبة مغلفة وساخنة بانتظار الطيار',
        icon: PackageCheck,
        accentColor: 'from-purple-500 to-indigo-600',
        bgLight: 'bg-purple-50 dark:bg-purple-950/30 text-purple-600 dark:text-purple-400 border-purple-200 dark:border-purple-800',
        activeText: 'تم الانتهاء من التجهيز والوجبة بانتظار استلام الطيار',
    },
    {
        key: 'OUT_FOR_DELIVERY',
        label: 'في الطريق إليك',
        sublabel: 'كابتن التوصيل استلم الطلب ومتجه لعنوانك',
        icon: Bike,
        accentColor: 'from-teal-500 to-emerald-600',
        bgLight: 'bg-teal-50 dark:bg-teal-950/30 text-teal-600 dark:text-teal-400 border-teal-200 dark:border-teal-800',
        activeText: 'الكابتن يقود دراجته باتجاه موقعك الآن',
    },
    {
        key: 'DELIVERED',
        label: 'تم الاستلام',
        sublabel: 'ألف هنا وشفاء! نتمنى لك إفطاراً رائعاً',
        icon: Sparkles,
        accentColor: 'from-emerald-500 to-green-600',
        bgLight: 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
        activeText: 'تم تسليم الوجبة بنجاح وبالهناء والشفاء',
    },
];

const statusOrderMap: Record<string, number> = {
    PENDING: 0,
    CONFIRMED: 1,
    PREPARING: 2,
    READY_FOR_PICKUP: 3,
    ASSIGNED_TO_DRIVER: 4,
    OUT_FOR_DELIVERY: 4,
    DELIVERED: 5,
};

const currentStepIndex = computed(() => statusOrderMap[props.order.status] ?? 0);
const currentStepObj = computed(() => steps[currentStepIndex.value] || steps[0]);
const isCancelled = computed(
    () => props.order.status === 'CANCELLED' || props.order.status === 'REJECTED' || props.order.status === 'FAILED',
);
const progressPercentage = computed(() =>
    isCancelled.value ? 0 : Math.round(((currentStepIndex.value + 1) / steps.length) * 100),
);
const showLiveMap = computed(
    () => !!driver.value && ['ASSIGNED_TO_DRIVER', 'OUT_FOR_DELIVERY'].includes(props.order.status),
);

const statusBadgeMap: Record<string, { label: string; bg: string; text: string; dot: string }> = {
    PENDING: { label: 'قيد الانتظار', bg: 'bg-amber-100 dark:bg-amber-950/60', text: 'text-amber-800 dark:text-amber-300', dot: 'bg-amber-500' },
    CONFIRMED: { label: 'تم التأكيد', bg: 'bg-blue-100 dark:bg-blue-950/60', text: 'text-blue-800 dark:text-blue-300', dot: 'bg-blue-500' },
    PREPARING: { label: 'المطعم يجهز الوجبة', bg: 'bg-orange-100 dark:bg-orange-950/60', text: 'text-orange-800 dark:text-orange-300', dot: 'bg-orange-500' },
    READY_FOR_PICKUP: { label: 'جاهز للاستلام', bg: 'bg-purple-100 dark:bg-purple-950/60', text: 'text-purple-800 dark:text-purple-300', dot: 'bg-purple-500' },
    ASSIGNED_TO_DRIVER: { label: 'تم تعيين الطيار', bg: 'bg-indigo-100 dark:bg-indigo-950/60', text: 'text-indigo-800 dark:text-indigo-300', dot: 'bg-indigo-500' },
    OUT_FOR_DELIVERY: { label: 'الطيار في الطريق إليك', bg: 'bg-teal-100 dark:bg-teal-950/60', text: 'text-teal-800 dark:text-teal-300', dot: 'bg-teal-500' },
    DELIVERED: { label: 'تم التوصيل بنجاح', bg: 'bg-emerald-100 dark:bg-emerald-950/60', text: 'text-emerald-800 dark:text-emerald-300', dot: 'bg-emerald-500' },
    CANCELLED: { label: 'طلب ملغي', bg: 'bg-red-100 dark:bg-red-950/60', text: 'text-red-800 dark:text-red-300', dot: 'bg-red-500' },
    REJECTED: { label: 'تم رفض الطلب', bg: 'bg-rose-100 dark:bg-rose-950/60', text: 'text-rose-800 dark:text-rose-300', dot: 'bg-rose-500' },
};

const getStatusMeta = (status: string) =>
    statusBadgeMap[status] || { label: status, bg: 'bg-stone-100', text: 'text-stone-700', dot: 'bg-stone-400' };

const handleCopyOrderNumber = async (): Promise<void> => {
    await navigator.clipboard.writeText(props.order.order_number);
    copied.value = true;
    window.setTimeout(() => {
        copied.value = false;
    }, 2000);
};

const handlePrint = (): void => {
    window.print();
};

const formatFullDate = (date: string): string =>
    new Date(date).toLocaleString('ar-EG', { dateStyle: 'full', timeStyle: 'short' });

const formatTime = (date: string): string =>
    new Date(date).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });

const optionLabel = (opt: Record<string, unknown>): string => {
    const optName = (opt.optionName || opt.option_name || 'خيار') as string;
    const valName = (opt.valueName || opt.value_name || (typeof opt === 'string' ? opt : '')) as string;
    return `${optName}: ${valName}`;
};

const addonLabel = (addon: Record<string, unknown> | string): string => {
    if (typeof addon === 'string') {
        return addon;
    }
    return (addon.name as string) || 'إضافة';
};

const waLink = computed(() => `https://wa.me/2${(driver.value?.phone || '').replace(/^0/, '')}`);
</script>

<template>
    <div class="px-4 py-4">
        <Head :title="`تفاصيل الطلب ${order.order_number}`" />

        <div class="space-y-8 max-w-6xl mx-auto">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-stone-900 via-stone-800 to-stone-950 text-white p-6 sm:p-8 shadow-xl border border-stone-800">
                <div class="absolute top-0 right-0 w-80 h-80 bg-orange-500/10 rounded-full blur-3xl pointer-events-none -mr-20 -mt-20" />
                <div class="absolute bottom-0 left-0 w-60 h-60 bg-amber-500/10 rounded-full blur-3xl pointer-events-none -ml-20 -mb-20" />

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="text-xs font-bold px-2.5 py-1 rounded-lg bg-white/10 text-stone-300 tracking-wider">طلب رقم</span>
                            <div class="flex items-center gap-2">
                                <h1 class="text-2xl sm:text-3xl font-black font-mono tracking-tight text-white">{{ order.order_number }}</h1>
                                <button
                                    type="button"
                                    class="p-1.5 rounded-lg bg-white/10 hover:bg-white/20 text-stone-300 hover:text-white transition"
                                    title="نسخ رقم الطلب"
                                    @click="handleCopyOrderNumber"
                                >
                                    <Check v-if="copied" class="w-4 h-4 text-emerald-400" />
                                    <Copy v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-black shadow-xs"
                                :class="[getStatusMeta(order.status).bg, getStatusMeta(order.status).text]"
                            >
                                <span class="w-2 h-2 rounded-full animate-pulse" :class="getStatusMeta(order.status).dot" />
                                <span>{{ getStatusMeta(order.status).label }}</span>
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 text-xs text-stone-400">
                            <span class="flex items-center gap-1.5">
                                <Calendar class="w-3.5 h-3.5 text-orange-400" />
                                <span>{{ formatFullDate(order.created_at) }}</span>
                            </span>
                            <span v-if="order.restaurant" class="flex items-center gap-1.5">
                                <Store class="w-3.5 h-3.5 text-amber-400" />
                                <span>مطعم: <strong class="text-stone-200">{{ order.restaurant.name }}</strong></span>
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                        <button
                            type="button"
                            class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-bold text-stone-200 transition flex items-center gap-1.5"
                            @click="handlePrint"
                        >
                            <Printer class="w-4 h-4" />
                            <span>طباعة الفاتورة</span>
                        </button>
                        <Link
                            href="/customer/orders"
                            class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-500 text-xs font-bold text-white shadow-md shadow-orange-600/30 transition flex items-center gap-1.5"
                        >
                            <span>سجل الطلبات</span>
                            <ArrowRight class="w-4 h-4 rotate-180" />
                        </Link>
                    </div>
                </div>
            </div>

            <div
                v-if="isCancelled"
                class="p-6 rounded-3xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 shadow-sm flex items-start gap-4 animate-fade-in"
            >
                <div class="p-3 rounded-2xl bg-red-500 text-white shadow-md shadow-red-500/20 shrink-0">
                    <AlertTriangle class="w-6 h-6" />
                </div>
                <div class="space-y-1">
                    <h3 class="font-black text-sm text-red-900 dark:text-red-200">تم إلغاء هذا الطلب</h3>
                    <p class="text-xs text-red-700 dark:text-red-300">
                        تم إلغاء الطلب من قبل الإدارة أو المطعم. يمكنك تقديم طلب جديد في أي وقت من قائمة المطاعم المتاحة.
                    </p>
                    <div class="pt-2">
                        <Link href="/restaurants" class="inline-flex items-center gap-1 text-xs font-bold text-red-600 dark:text-red-400 hover:underline">
                            <span>تصفح المطاعم واطلب وجبة أخرى</span>
                            <ArrowRight class="w-3.5 h-3.5 rotate-180" />
                        </Link>
                    </div>
                </div>
            </div>

            <div
                v-if="!isCancelled"
                class="p-6 sm:p-8 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200/80 dark:border-stone-800 shadow-sm space-y-6"
            >
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-stone-100 dark:border-stone-800">
                    <div>
                        <h2 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-2">
                            <UtensilsCrossed class="w-5 h-5 text-orange-500" />
                            <span>مراحل تجهيز وتوصيل طلبك المباشرة</span>
                        </h2>
                        <p class="text-xs text-stone-400 mt-0.5">متابعة حية من لحظة قبول الأوردر حتى وصوله لباب منزلك أو سكنك</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="text-xs text-stone-500 font-semibold">
                            نسبة الإنجاز: <strong class="text-orange-600">{{ progressPercentage }}%</strong>
                        </div>
                        <div class="w-24 h-2 rounded-full bg-stone-100 dark:bg-stone-800 overflow-hidden">
                            <div
                                class="h-full bg-gradient-to-r from-orange-500 to-amber-500 transition-all duration-700 ease-out rounded-full"
                                :style="{ width: `${progressPercentage}%` }"
                            />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4 relative">
                    <div
                        v-for="(step, idx) in steps"
                        :key="step.key"
                        class="relative p-4 rounded-2xl border transition-all duration-300 flex flex-col justify-between"
                        :class="
                            currentStepIndex === idx
                                ? 'bg-gradient-to-b from-orange-500/10 via-amber-500/5 to-transparent border-orange-500 dark:border-orange-500/80 shadow-lg shadow-orange-500/10 scale-[1.03] z-10 ring-2 ring-orange-500/20'
                                : currentStepIndex > idx
                                  ? 'bg-stone-50/70 dark:bg-stone-800/40 border-emerald-500/30 text-stone-700 dark:text-stone-300'
                                  : 'bg-stone-50/40 dark:bg-stone-900/40 border-stone-200/60 dark:border-stone-800/60 opacity-60'
                        "
                    >
                        <div class="flex items-center justify-between mb-3">
                            <span
                                class="w-5 h-5 rounded-full text-[10px] font-black flex items-center justify-center"
                                :class="
                                    currentStepIndex > idx
                                        ? 'bg-emerald-500 text-white'
                                        : currentStepIndex === idx
                                          ? 'bg-orange-600 text-white animate-bounce'
                                          : 'bg-stone-200 dark:bg-stone-800 text-stone-500'
                                "
                            >
                                <Check v-if="currentStepIndex > idx" class="w-3 h-3 stroke-[3]" />
                                <template v-else>{{ idx + 1 }}</template>
                            </span>

                            <span
                                v-if="currentStepIndex === idx"
                                class="inline-flex items-center gap-1 text-[9px] font-black uppercase tracking-wider text-orange-600 dark:text-orange-400 bg-orange-100 dark:bg-orange-950/80 px-1.5 py-0.5 rounded-md animate-pulse"
                            >
                                الآن
                            </span>
                        </div>

                        <div
                            class="w-11 h-11 rounded-2xl flex items-center justify-center mb-3 shadow-xs transition-transform"
                            :class="
                                currentStepIndex === idx
                                    ? 'bg-gradient-to-tr from-orange-500 to-amber-500 text-white shadow-md shadow-orange-500/30 scale-110'
                                    : currentStepIndex > idx
                                      ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400'
                                      : 'bg-stone-100 dark:bg-stone-800 text-stone-400'
                            "
                        >
                            <component :is="step.icon" class="w-5 h-5" />
                        </div>

                        <div class="space-y-1">
                            <h3
                                class="text-xs font-black leading-tight"
                                :class="
                                    currentStepIndex === idx
                                        ? 'text-orange-600 dark:text-orange-400 text-sm'
                                        : currentStepIndex > idx
                                          ? 'text-stone-900 dark:text-white'
                                          : 'text-stone-500'
                                "
                            >
                                {{ step.label }}
                            </h3>
                            <p class="text-[10px] text-stone-500 dark:text-stone-400 leading-snug line-clamp-2">{{ step.sublabel }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-orange-50 via-amber-50 to-orange-50/50 dark:from-stone-800/90 dark:via-stone-800/60 dark:to-stone-800/90 border border-orange-200 dark:border-orange-900/40 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-2xl bg-orange-600 text-white flex items-center justify-center font-bold shrink-0 shadow-md shadow-orange-600/20">
                            <component :is="currentStepObj.icon" class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] font-bold text-orange-600 dark:text-orange-400">المرحلة الحالية للطلب:</span>
                                <span class="text-xs font-black text-stone-900 dark:text-white">{{ currentStepObj.label }}</span>
                            </div>
                            <p class="text-xs text-stone-700 dark:text-stone-300 font-medium mt-0.5">{{ currentStepObj.activeText }}</p>
                        </div>
                    </div>

                    <button
                        v-if="order.statusHistories && order.statusHistories.length > 0"
                        type="button"
                        class="text-xs font-bold text-orange-600 dark:text-orange-400 hover:text-orange-700 flex items-center gap-1.5 self-start sm:self-center px-3 py-1.5 rounded-xl bg-orange-100/70 dark:bg-orange-950/60 transition"
                        @click="showHistory = !showHistory"
                    >
                        <span>سجل الأحداث ({{ order.statusHistories.length }})</span>
                        <ChevronUp v-if="showHistory" class="w-3.5 h-3.5" />
                        <ChevronDown v-else class="w-3.5 h-3.5" />
                    </button>
                </div>

                <div
                    v-if="showHistory && order.statusHistories && order.statusHistories.length > 0"
                    class="p-5 rounded-2xl bg-stone-50 dark:bg-stone-800/50 border border-stone-200/80 dark:border-stone-700/60 space-y-3 animate-fade-in"
                >
                    <h4 class="text-xs font-black text-stone-900 dark:text-white flex items-center gap-1.5">
                        <Clock class="w-4 h-4 text-orange-500" />
                        <span>السجل الزمني لتحديثات الطلب:</span>
                    </h4>
                    <div class="space-y-2.5 pt-2">
                        <div v-for="history in order.statusHistories" :key="history.id" class="flex items-start gap-3 text-xs">
                            <span class="w-2 h-2 rounded-full bg-orange-500 mt-1.5 shrink-0" />
                            <div class="flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-bold text-stone-800 dark:text-stone-200">{{ history.status }}</span>
                                    <span class="text-[11px] text-stone-400">{{ formatTime(history.created_at) }}</span>
                                </div>
                                <p v-if="history.notes" class="text-[11px] text-stone-500 mt-0.5">{{ history.notes }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="driver"
                class="p-4 sm:p-5 rounded-3xl bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 text-white shadow-lg flex flex-col sm:flex-row sm:items-center justify-between gap-4 animate-fade-in"
            >
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center font-black text-xl shrink-0 shadow-md">
                        <Bike class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-bold text-emerald-200">كابتن التوصيل المكلف بالطلب:</span>
                            <span class="px-2 py-0.5 rounded-md bg-white/20 text-white text-[10px] font-black">
                                {{ order.status === 'OUT_FOR_DELIVERY' ? 'في الطريق إليك' : 'تم التعيين' }}
                            </span>
                        </div>
                        <h3 class="text-base font-black text-white mt-0.5">{{ driver.name }}</h3>
                        <p class="text-xs text-emerald-100 mt-0.5 flex items-center gap-1.5">
                            <span>رقم هاتف الكابتن:</span>
                            <span class="font-mono font-bold text-white text-sm bg-black/20 px-2 py-0.5 rounded-md">{{ driver.phone }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 shrink-0">
                    <a
                        :href="`tel:${driver.phone || ''}`"
                        class="px-4 py-2.5 rounded-2xl bg-white text-emerald-700 hover:bg-emerald-50 font-black text-xs shadow-md transition flex items-center gap-2 active:scale-95"
                    >
                        <Phone class="w-4 h-4 text-emerald-600" />
                        <span>اتصال بالكابتن</span>
                    </a>
                    <a
                        :href="waLink"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="px-4 py-2.5 rounded-2xl bg-white/20 hover:bg-white/30 text-white font-bold text-xs backdrop-blur-md transition flex items-center gap-1.5 active:scale-95"
                    >
                        <span>واتساب</span>
                    </a>
                </div>
            </div>

            <CustomerLiveTrackingMap
                v-if="showLiveMap && driver"
                :order-number="order.order_number"
                :customer-lat="order.latitude ? Number(order.latitude) : null"
                :customer-lng="order.longitude ? Number(order.longitude) : null"
                :customer-address="order.address"
                :restaurant-lat="order.restaurant?.latitude ? Number(order.restaurant.latitude) : null"
                :restaurant-lng="order.restaurant?.longitude ? Number(order.restaurant.longitude) : null"
                :restaurant-name="order.restaurant?.name || 'المطعم'"
                :initial-driver-lat="driver.current_latitude ? Number(driver.current_latitude) : null"
                :initial-driver-lng="driver.current_longitude ? Number(driver.current_longitude) : null"
                :driver-name="driver.name"
                :driver-phone="driver.phone"
                :order-status="order.status"
            />

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-6">
                    <div class="p-6 sm:p-7 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs space-y-5">
                        <div class="flex items-center justify-between pb-4 border-b border-stone-100 dark:border-stone-800">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-orange-100 dark:bg-orange-950 text-orange-600 flex items-center justify-center font-bold">
                                    <UtensilsCrossed class="w-5 h-5" />
                                </div>
                                <div>
                                    <h2 class="text-base font-black text-stone-900 dark:text-white">أصناف الوجبات المطلوبة</h2>
                                    <p class="text-xs text-stone-400">إجمالي {{ order.items?.length || 0 }} أصناف مختلفة</p>
                                </div>
                            </div>

                            <div v-if="order.restaurant" class="text-left">
                                <span class="text-[10px] text-stone-400 block">المطعم المصدر</span>
                                <span class="text-xs font-bold text-orange-600">{{ order.restaurant.name }}</span>
                            </div>
                        </div>

                        <div class="divide-y divide-stone-100 dark:divide-stone-800">
                            <div v-for="item in order.items" :key="item.id" class="py-4.5 flex items-start justify-between gap-4">
                                <div class="space-y-1.5 min-w-0">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-7 h-7 rounded-xl bg-orange-100 dark:bg-orange-950/80 text-orange-600 dark:text-orange-400 flex items-center justify-center font-black text-xs shrink-0 border border-orange-200/50 dark:border-orange-900/40">
                                            {{ item.quantity }}×
                                        </span>
                                        <span class="font-extrabold text-sm text-stone-900 dark:text-white">{{ item.name }}</span>
                                    </div>

                                    <div
                                        v-if="(Array.isArray(item.selected_options) && item.selected_options.length) || (Array.isArray(item.selected_addons) && item.selected_addons.length)"
                                        class="flex flex-wrap items-center gap-1.5 pt-1.5"
                                    >
                                        <span
                                            v-for="(opt, i) in item.selected_options || []"
                                            :key="`opt-${i}`"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-stone-100 dark:bg-stone-800 text-[11px] font-semibold text-stone-600 dark:text-stone-300 border border-stone-200/60 dark:border-stone-700"
                                        >
                                            <span>{{ optionLabel(opt as Record<string, unknown>) }}</span>
                                            <span v-if="Number((opt as any).price || 0) > 0" class="text-orange-600 font-bold">+{{ (opt as any).price }} ج.م</span>
                                        </span>
                                        <span
                                            v-for="(addon, i) in item.selected_addons || []"
                                            :key="`addon-${i}`"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-orange-50 dark:bg-orange-950/40 text-[11px] font-semibold text-orange-700 dark:text-orange-300 border border-orange-200/60 dark:border-orange-900/40"
                                        >
                                            <span>+ {{ addonLabel(addon as any) }}</span>
                                            <span v-if="Number((addon as any).price || 0) > 0" class="font-bold">({{ (addon as any).price }} ج.م)</span>
                                        </span>
                                    </div>

                                    <p
                                        v-if="item.notes"
                                        class="text-xs text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/30 px-2.5 py-1 rounded-lg inline-block border border-amber-200/60 dark:border-amber-900/40 mt-1"
                                    >
                                        ملاحظة: {{ item.notes }}
                                    </p>
                                </div>

                                <div class="text-left shrink-0">
                                    <span class="font-black text-sm text-stone-900 dark:text-white block">{{ Number(item.total_price).toFixed(2) }} ج.م</span>
                                    <span class="text-[10px] text-stone-400">({{ Number(item.unit_price).toFixed(2) }} للقطعة)</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs space-y-4">
                        <h3 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-2">
                            <MapPin class="w-5 h-5 text-orange-500" />
                            <span>وجهة التوصيل والملاحظات</span>
                        </h3>

                        <div class="p-4 rounded-2xl bg-stone-50 dark:bg-stone-800/60 border border-stone-200/70 dark:border-stone-700/60 space-y-2.5">
                            <div>
                                <span class="text-[11px] font-bold text-stone-400 block mb-0.5">عنوان التوصيل المحدد في برج العرب:</span>
                                <p class="text-xs font-semibold text-stone-800 dark:text-stone-200 leading-relaxed">
                                    {{ order.address || 'عنوان العميل الافتراضي' }}
                                </p>
                            </div>

                            <div v-if="order.customer_notes" class="pt-2 border-t border-stone-200/60 dark:border-stone-700">
                                <span class="text-[11px] font-bold text-stone-400 block mb-0.5">ملاحظات خاصة للكابتن أو المطعم:</span>
                                <p class="text-xs italic text-stone-600 dark:text-stone-300">"{{ order.customer_notes }}"</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div
                        v-if="driver"
                        class="p-6 rounded-3xl bg-gradient-to-br from-emerald-500/10 via-teal-500/5 to-transparent border border-emerald-500/30 shadow-sm space-y-4"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-black text-lg shadow-md shadow-emerald-600/20">
                                <Bike class="w-6 h-6" />
                            </div>
                            <div>
                                <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wider block">كابتن التوصيل المكلف</span>
                                <h4 class="font-extrabold text-sm text-stone-900 dark:text-white">{{ driver.name }}</h4>
                                <span class="text-[10px] text-stone-400">جاهز لتوصيل طلبك ساخناً</span>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-white dark:bg-stone-800/80 border border-emerald-200 dark:border-emerald-900/40 space-y-1">
                            <span class="text-[10px] font-bold text-stone-400 block">رقم هاتف الكابتن:</span>
                            <span class="text-base font-black text-emerald-700 dark:text-emerald-300 font-mono tracking-wider block">{{ driver.phone }}</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <a
                                :href="`tel:${driver.phone || ''}`"
                                class="w-full py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-md shadow-emerald-600/20 transition flex items-center justify-center gap-2 active:scale-98"
                            >
                                <Phone class="w-4 h-4" />
                                <span>اتصال بالكابتن</span>
                            </a>
                            <a
                                :href="waLink"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="w-full py-2.5 px-3 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-200 text-xs font-black transition flex items-center justify-center gap-1.5"
                            >
                                <span>واتساب الكابتن</span>
                            </a>
                        </div>
                    </div>
                    <div
                        v-else
                        class="p-5 rounded-3xl bg-stone-50 dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs flex items-center gap-3"
                    >
                        <div class="w-10 h-10 rounded-2xl bg-stone-200 dark:bg-stone-800 text-stone-500 flex items-center justify-center">
                            <Bike class="w-5 h-5" />
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-stone-900 dark:text-white">كابتن التوصيل</h4>
                            <p class="text-[11px] text-stone-400">سيتم تعيين أقرب كابتن فور اكتمال التجهيز</p>
                        </div>
                    </div>

                    <div
                        v-if="order.restaurant"
                        class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs space-y-3"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-orange-500/10 text-orange-600 flex items-center justify-center font-bold">
                                <Store class="w-5 h-5" />
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-xs text-stone-900 dark:text-white truncate">{{ order.restaurant.name }}</h4>
                                <p class="text-[10px] text-stone-400 truncate">{{ order.restaurant.address || 'برج العرب التكنولوجية' }}</p>
                            </div>
                        </div>

                        <a
                            v-if="order.restaurant.phone"
                            :href="`tel:${order.restaurant.phone}`"
                            class="w-full py-2.5 px-3 rounded-xl bg-stone-100 dark:bg-stone-800 hover:bg-stone-200 dark:hover:bg-stone-700 text-stone-700 dark:text-stone-300 text-xs font-bold transition flex items-center justify-center gap-2"
                        >
                            <Phone class="w-3.5 h-3.5 text-orange-500" />
                            <span>الاتصال بالمطعم: {{ order.restaurant.phone }}</span>
                        </a>
                    </div>

                    <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs space-y-3.5 text-xs">
                        <h3 class="text-base font-black text-stone-900 dark:text-white pb-3 border-b border-stone-100 dark:border-stone-800 flex items-center justify-between">
                            <span>الفاتورة والحساب</span>
                            <CreditCard class="w-4 h-4 text-stone-400" />
                        </h3>

                        <div class="flex items-center justify-between text-stone-500">
                            <span>المجموع الفرعي للأصناف</span>
                            <span class="font-bold text-stone-800 dark:text-stone-200">{{ Number(order.subtotal).toFixed(2) }} ج.م</span>
                        </div>

                        <div
                            v-if="Number(order.student_discount_amount) > 0"
                            class="flex items-center justify-between text-emerald-600 dark:text-emerald-400 font-bold bg-emerald-50 dark:bg-emerald-950/30 p-2 rounded-xl border border-emerald-200/50 dark:border-emerald-900/40"
                        >
                            <span class="flex items-center gap-1.5">
                                <GraduationCap class="w-4 h-4" />
                                خصم الطلاب (BATU)
                            </span>
                            <span>-{{ Number(order.student_discount_amount).toFixed(2) }} ج.م</span>
                        </div>

                        <div class="flex items-center justify-between text-stone-500">
                            <span>رسوم التوصيل</span>
                            <span class="font-bold text-stone-800 dark:text-stone-200">{{ Number(order.delivery_fee).toFixed(2) }} ج.م</span>
                        </div>

                        <div class="pt-3 border-t border-stone-100 dark:border-stone-800 flex items-center justify-between">
                            <span class="text-sm font-black text-stone-900 dark:text-white">المبلغ المطلوب سداده</span>
                            <div class="text-left">
                                <span class="text-2xl font-black bg-gradient-to-r from-orange-600 to-amber-500 bg-clip-text text-transparent">
                                    {{ Number(order.total_amount).toFixed(2) }}
                                </span>
                                <span class="text-xs font-bold text-stone-500 mr-1">ج.م</span>
                            </div>
                        </div>

                        <div class="pt-2 flex items-center justify-center gap-1.5 text-[11px] text-stone-500 bg-stone-50 dark:bg-stone-800/40 p-2.5 rounded-xl border border-stone-100 dark:border-stone-800">
                            <ShieldCheck class="w-4 h-4 text-emerald-500 shrink-0" />
                            <span>الدفع نقداً للكابتن عند الاستلام (Cash on Delivery)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
