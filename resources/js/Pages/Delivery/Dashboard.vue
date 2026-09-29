<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DeliveryLayout from '../../Layouts/DeliveryLayout.vue';
import type { DeliveryDriver, Order } from '../../Types';
import {
    Bike,
    Phone,
    MapPin,
    ArrowRight,
    Store,
    DollarSign,
    Power,
    Navigation,
    Calendar,
    History,
    CheckCircle2,
} from '@lucide/vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = withDefaults(
    defineProps<{
        driver: DeliveryDriver;
        active_orders?: Order[];
        completed_today?: number;
        earnings_today?: number;
        weekly_stats?: { date: string; orders: number }[];
    }>(),
    {
        active_orders: () => [],
        completed_today: 0,
        earnings_today: 0,
        weekly_stats: () => [],
    },
);

const isAvailable = computed(() => props.driver.availability_status === 'AVAILABLE');

const chartData = computed(() => ({
    labels: props.weekly_stats.map((d) => d.date),
    datasets: [
        {
            label: 'تم التوصيل',
            data: props.weekly_stats.map((d) => d.orders),
            backgroundColor: '#10b981',
            borderRadius: 4,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#1c1917',
            titleColor: '#fff',
            bodyColor: '#fff',
            borderColor: '#292524',
            borderWidth: 1,
            callbacks: {
                label: (ctx: { raw: unknown }) => `${ctx.raw} طلبات`,
            },
        },
    },
    scales: {
        x: { ticks: { font: { size: 10 } } },
        y: { ticks: { font: { size: 10 }, stepSize: 1 }, beginAtZero: true },
    },
};

const toggleStatus = (): void => {
    router.post('/delivery/profile/availability', {
        availability_status: isAvailable.value ? 'OFFLINE' : 'AVAILABLE',
    });
};

const handleUpdateStatus = (orderId: number, nextStatus: 'OUT_FOR_DELIVERY' | 'DELIVERED'): void => {
    router.patch(`/delivery/orders/${orderId}/status`, { status: nextStatus });
};
</script>

<template>
    <DeliveryLayout title="لوحة كابتن التوصيل" :is-available="isAvailable">
        <Head title="لوحة كابتن التوصيل" />

        <div class="space-y-6">
            <section class="relative isolate overflow-hidden rounded-[2rem] bg-emerald-950 px-5 py-6 text-white shadow-xl shadow-emerald-950/20 sm:px-7">
                <div class="absolute -left-12 top-0 h-40 w-40 rounded-full bg-emerald-400/20 blur-3xl" />
                <div class="absolute -bottom-16 right-10 h-48 w-48 rounded-full bg-teal-300/10 blur-3xl" />
                <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="grid h-14 w-14 place-items-center rounded-2xl border border-white/15 bg-white/10 shadow-inner">
                            <Navigation class="h-7 w-7 text-emerald-300" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black tracking-[.22em] text-emerald-300 uppercase">وضع الرحلة</p>
                            <h1 class="mt-1 text-xl font-black">{{ isAvailable ? 'أنت جاهز للطريق' : 'أنت غير متصل الآن' }}</h1>
                            <p class="mt-1 text-xs text-emerald-100/70">
                                {{ active_orders.length ? `لديك ${active_orders.length} طلب يحتاج متابعة الآن` : 'شغّل الاستقبال لتصلك الطلبات الجديدة فورًا' }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        :class="[
                            'rounded-2xl px-4 py-3 text-xs font-black transition',
                            isAvailable ? 'bg-white text-emerald-950 hover:bg-emerald-50' : 'bg-emerald-400 text-emerald-950 hover:bg-emerald-300',
                        ]"
                        @click="toggleStatus"
                    >
                        {{ isAvailable ? 'إيقاف الاستقبال' : 'بدء الاستقبال' }}
                    </button>
                </div>
                <div class="relative mt-6 grid grid-cols-3 divide-x divide-x-reverse divide-white/10 rounded-2xl border border-white/10 bg-black/10 text-center">
                    <div class="px-2 py-3">
                        <p class="text-lg font-black">{{ active_orders.length }}</p>
                        <p class="text-[10px] text-emerald-100/60">طلبات نشطة</p>
                    </div>
                    <div class="px-2 py-3">
                        <p class="text-lg font-black">{{ completed_today }}</p>
                        <p class="text-[10px] text-emerald-100/60">تم اليوم</p>
                    </div>
                    <div class="px-2 py-3">
                        <p class="text-lg font-black">{{ Number(earnings_today).toLocaleString() }}</p>
                        <p class="text-[10px] text-emerald-100/60">كاش اليوم</p>
                    </div>
                </div>
            </section>

            <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold text-2xl shadow-lg shadow-emerald-600/20">
                        <Bike class="w-8 h-8" />
                    </div>
                    <div>
                        <h2 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-2">
                            <span>{{ driver.name }}</span>
                            <span
                                :class="[
                                    'text-[10px] px-2.5 py-0.5 rounded-full font-bold',
                                    isAvailable
                                        ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300'
                                        : 'bg-stone-100 dark:bg-stone-800 text-stone-500',
                                ]"
                            >
                                {{ isAvailable ? 'جاهز لتلقي الطلبات' : 'غير متصل' }}
                            </span>
                        </h2>
                        <p class="text-xs text-stone-500 mt-0.5">
                            مطعم: {{ driver.restaurant?.name || 'وصلة — كابتن حر' }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="text-center px-4 py-2 bg-stone-50 dark:bg-stone-800 rounded-2xl border border-stone-200 dark:border-stone-700">
                        <span class="text-[10px] text-stone-400 block font-bold">تم توصيلها اليوم</span>
                        <span class="text-lg font-black text-emerald-600 dark:text-emerald-400">{{ completed_today }} طلبات</span>
                    </div>
                    <div class="text-center px-4 py-2 bg-stone-50 dark:bg-stone-800 rounded-2xl border border-stone-200 dark:border-stone-700">
                        <span class="text-[10px] text-stone-400 block font-bold">كاش اليوم</span>
                        <span class="text-lg font-black text-amber-600 dark:text-amber-400">{{ Number(earnings_today).toLocaleString() }} ج.م</span>
                    </div>
                    <button
                        type="button"
                        :class="[
                            'p-3 rounded-2xl border font-bold text-xs flex items-center gap-2 transition',
                            isAvailable
                                ? 'border-red-300 dark:border-red-900/50 bg-red-50 dark:bg-red-950/30 text-red-600'
                                : 'border-emerald-300 dark:border-emerald-900/50 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600',
                        ]"
                        @click="toggleStatus"
                    >
                        <Power class="w-4 h-4" />
                        <span>{{ isAvailable ? 'إيقاف الاستقبال' : 'تفعيل الاستقبال' }}</span>
                    </button>
                </div>
            </div>

            <div
                v-if="weekly_stats.length > 0"
                class="p-5 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs"
            >
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <Calendar class="w-4 h-4 text-emerald-600" />
                        <h3 class="text-xs font-bold text-stone-800 dark:text-stone-200">نشاط توصيلاتي خلال الأسبوع الماضي</h3>
                    </div>
                    <Link
                        href="/delivery/order-history"
                        class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1"
                    >
                        <History class="w-3.5 h-3.5" />
                        <span>عرض سجل كل الطلبات</span>
                    </Link>
                </div>
                <div class="h-28 w-full">
                    <Bar :data="chartData" :options="chartOptions" />
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-2">
                        <Navigation class="w-4 h-4 text-emerald-600" />
                        <span>الطلبات المكلف بها حالياً ({{ active_orders.length }})</span>
                    </h2>
                    <Link
                        href="/delivery/order-history"
                        class="px-3 py-1.5 rounded-xl border border-stone-200 dark:border-stone-700 text-xs font-bold text-stone-600 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-800 transition flex items-center gap-1.5"
                    >
                        <History class="w-3.5 h-3.5" />
                        <span>سجل الطلبات بالكامل</span>
                    </Link>
                </div>

                <div
                    v-if="active_orders.length === 0"
                    class="text-center py-16 bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-8"
                >
                    <Bike class="w-14 h-14 text-stone-300 dark:text-stone-600 mx-auto mb-3" />
                    <h3 class="text-base font-bold text-stone-700 dark:text-stone-300 mb-1">لا توجد طلبات جارية الآن</h3>
                    <p class="text-xs text-stone-400">
                        {{ isAvailable ? 'أنت متصل وجاهز، سيظهر أي طلب يتم تكليفك به هنا فوراً!' : 'فعل حالة التوفر لاستقبال طلبات التوصيل الجديدة.' }}
                    </p>
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-for="order in active_orders"
                        :key="order.id"
                        class="p-6 rounded-3xl bg-white dark:bg-stone-900 border-2 border-emerald-500 shadow-md space-y-4"
                    >
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-stone-100 dark:border-stone-800">
                            <div>
                                <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ order.order_number }}</span>
                                <h3 class="font-extrabold text-base text-stone-900 dark:text-white mt-0.5">
                                    مطعم: {{ order.restaurant?.name }}
                                </h3>
                            </div>
                            <span
                                :class="[
                                    'px-3 py-1 rounded-full text-xs font-bold w-fit',
                                    order.status === 'OUT_FOR_DELIVERY'
                                        ? 'bg-teal-100 dark:bg-teal-950 text-teal-700 dark:text-teal-300'
                                        : 'bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300',
                                ]"
                            >
                                {{ order.status === 'OUT_FOR_DELIVERY' ? 'في الطريق للعميل' : 'تم التكليف — جاهز للاستلام' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                            <div class="p-3.5 rounded-2xl bg-stone-50 dark:bg-stone-800/60 border border-stone-200 dark:border-stone-700 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-stone-700 dark:text-stone-300 flex items-center gap-1.5">
                                        <Store class="w-3.5 h-3.5 text-orange-500" />
                                        نقطة الاستلام (المطعم)
                                    </span>
                                    <a
                                        v-if="order.restaurant?.phone"
                                        :href="`tel:${order.restaurant.phone}`"
                                        class="text-orange-600 font-bold hover:underline flex items-center gap-1"
                                    >
                                        <Phone class="w-3 h-3" /> اتصال
                                    </a>
                                </div>
                                <p class="text-stone-500">{{ order.restaurant?.address }}</p>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-stone-50 dark:bg-stone-800/60 border border-stone-200 dark:border-stone-700 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-stone-700 dark:text-stone-300 flex items-center gap-1.5">
                                        <MapPin class="w-3.5 h-3.5 text-emerald-500" />
                                        نقطة التسليم (العميل)
                                    </span>
                                    <a
                                        v-if="order.customer?.user?.phone"
                                        :href="`tel:${order.customer.user.phone}`"
                                        class="text-emerald-600 font-bold hover:underline flex items-center gap-1"
                                    >
                                        <Phone class="w-3 h-3" /> اتصال
                                    </a>
                                </div>
                                <p class="text-stone-800 dark:text-stone-200 font-medium">{{ order.address }}</p>
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl bg-amber-50 dark:bg-stone-800 border border-amber-300 dark:border-stone-700 flex items-center justify-between">
                            <div class="flex items-center gap-2 text-xs">
                                <DollarSign class="w-4 h-4 text-amber-600" />
                                <span class="font-bold text-stone-800 dark:text-stone-200">المبلغ المطلوب تحصيله كاش من العميل:</span>
                            </div>
                            <span class="text-xl font-black text-amber-600 dark:text-amber-400">{{ order.total_amount }} ج.م</span>
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-3 pt-2">
                            <Link
                                :href="`/delivery/orders/${order.id}`"
                                class="px-4 py-2.5 rounded-xl border border-stone-300 dark:border-stone-700 text-xs font-bold text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800 transition"
                            >
                                الخريطة والتعليمات
                            </Link>
                            <button
                                v-if="order.status === 'ASSIGNED_TO_DRIVER'"
                                type="button"
                                class="px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-md transition"
                                @click="handleUpdateStatus(order.id, 'OUT_FOR_DELIVERY')"
                            >
                                استلمت من المطعم وفي الطريق
                            </button>
                            <button
                                v-if="order.status === 'OUT_FOR_DELIVERY'"
                                type="button"
                                class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md transition flex items-center gap-1.5"
                                @click="handleUpdateStatus(order.id, 'DELIVERED')"
                            >
                                <CheckCircle2 class="w-4 h-4" />
                                <span>تم التسليم وتحصيل الكاش</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DeliveryLayout>
</template>
