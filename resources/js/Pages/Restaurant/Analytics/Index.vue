<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    DollarSign,
    TrendingUp,
    ShoppingBag,
    CheckCircle2,
    XCircle,
    Clock,
    Award,
    Calendar,
    ArrowUpRight,
    ArrowDownRight,
    Percent,
    BarChart3,
} from '@lucide/vue';

interface Stats {
    total_orders: number;
    delivered_orders: number;
    cancelled_orders: number;
    pending_orders: number;
    preparing_orders: number;
    gross_revenue: number;
    delivery_fees: number;
    platform_cut: number;
    net_earnings: number;
    avg_order_value: number;
    cancellation_rate: number;
    completion_rate: number;
    this_month_revenue: number;
    last_month_revenue: number;
    this_month_orders: number;
    last_month_orders: number;
}

interface DailySale {
    date: string;
    revenue: number;
    delivered_count: number;
    cancelled_count: number;
}

interface MonthlySummary {
    month: string;
    month_short: string;
    year: string;
    revenue: number;
    net_earnings: number;
    delivered_orders: number;
    cancelled_orders: number;
}

interface TopItem {
    name: string;
    qty: number;
    revenue: number;
}

interface RestaurantInfo {
    id: number;
    name: string;
    commission_type: string;
    commission_percentage: number;
    monthly_subscription_fee: number;
}

const props = withDefaults(
    defineProps<{
        stats: Stats;
        daily_sales?: DailySale[];
        monthly_summary?: MonthlySummary[];
        top_items?: TopItem[];
        restaurant: RestaurantInfo;
    }>(),
    {
        daily_sales: () => [],
        monthly_summary: () => [],
        top_items: () => [],
    },
);

const salesView = ref<'revenue' | 'orders'>('revenue');

const fmt = (v: number | string | null | undefined): string => {
    const n = Number(v ?? 0);
    if (isNaN(n)) {
        return '0';
    }
    return Math.round(n).toLocaleString('en');
};

const momRevenueGrowth = computed(() =>
    props.stats?.last_month_revenue > 0
        ? Math.round(
              ((props.stats.this_month_revenue - props.stats.last_month_revenue) /
                  props.stats.last_month_revenue) *
                  100,
          )
        : null,
);

const maxDailyRevenue = computed(() =>
    Math.max(...(props.daily_sales?.map((d) => d.revenue) || [1]), 1),
);
const maxDailyOrders = computed(() =>
    Math.max(...(props.daily_sales?.map((d) => d.delivered_count) || [1]), 1),
);

const barHeight = (day: DailySale): number => {
    const val = salesView.value === 'revenue' ? day.revenue : day.delivered_count;
    const maxVal = salesView.value === 'revenue' ? maxDailyRevenue.value : maxDailyOrders.value;
    return maxVal > 0 ? Math.max(8, Math.round((val / maxVal) * 100)) : 8;
};

const monthCompRate = (m: MonthlySummary): number => {
    const totalMOrders = m.delivered_orders + m.cancelled_orders;
    return totalMOrders > 0 ? Math.round((m.delivered_orders / totalMOrders) * 100) : 0;
};
</script>

<template>
    <Head title="التقارير والتحليلات — بوابة المطعم" />

    <div class="space-y-8 max-w-7xl mx-auto pb-12">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-orange-500/10 via-amber-500/5 to-transparent p-6 rounded-3xl border border-orange-500/15">
            <div>
                <div class="flex items-center gap-3">
                    <span class="p-2.5 rounded-2xl bg-orange-500 text-white shadow-lg shadow-orange-500/30">
                        <BarChart3 class="w-6 h-6" />
                    </span>
                    <div>
                        <h1 class="text-2xl font-black text-stone-900 dark:text-white">
                            أداء وأرباح مطعمك: {{ restaurant?.name }}
                        </h1>
                        <p class="text-sm text-stone-500 dark:text-stone-400 mt-0.5">
                            تفصيل شامل لجميع الطلبات، الأرباح الصافية، والنسب التشغيلية
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 self-start md:self-auto bg-white dark:bg-stone-900 px-4 py-2.5 rounded-2xl border border-stone-200 dark:border-stone-800 text-xs font-bold shadow-xs">
                <Percent class="w-4 h-4 text-orange-500" />
                <span class="text-stone-500 dark:text-stone-400">نظام العمولة:</span>
                <span class="text-stone-900 dark:text-white">
                    <template v-if="restaurant?.commission_type === 'PERCENTAGE'">
                        {{ restaurant.commission_percentage }}% عمولة على الطلب
                    </template>
                    <template v-else-if="restaurant?.commission_type === 'FIXED_PER_ORDER'">
                        {{ fmt(restaurant.commission_percentage) }} ج.م لكل طلب
                    </template>
                    <template v-else>
                        اشتراك شهري ({{ fmt(restaurant?.monthly_subscription_fee) }} ج.م)
                    </template>
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="relative overflow-hidden p-6 rounded-3xl bg-gradient-to-br from-emerald-500 to-teal-700 text-white shadow-xl shadow-emerald-500/20">
                <div class="flex items-center justify-between opacity-85 mb-2">
                    <span class="text-xs font-bold tracking-wide uppercase">صافي أرباح المطعم</span>
                    <span class="p-2 rounded-xl bg-white/15 backdrop-blur-xs">
                        <DollarSign class="w-4 h-4 text-stone-900" />
                    </span>
                </div>
                <div class="flex items-baseline gap-1.5 mt-1">
                    <span class="text-3xl font-black">{{ fmt(stats?.net_earnings) }}</span>
                    <span class="text-base font-bold opacity-80">ج.م</span>
                </div>
                <p class="text-[11px] opacity-75 mt-2">
                    بعد خصم مصاريف التوصيل وعمولة المنصة
                </p>
            </div>

            <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                <div class="flex items-center justify-between text-xs text-stone-500 dark:text-stone-400 font-bold mb-2">
                    <span>إجمالي المبيعات (Gross)</span>
                    <span class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600">
                        <ShoppingBag class="w-4 h-4" />
                    </span>
                </div>
                <div class="flex items-baseline gap-1.5 mt-1">
                    <span class="text-3xl font-black text-stone-900 dark:text-white">
                        {{ fmt(stats?.gross_revenue) }}
                    </span>
                    <span class="text-sm font-bold text-stone-400">ج.م</span>
                </div>
                <div class="flex items-center gap-2 mt-2 text-[11px] text-stone-500 dark:text-stone-400">
                    <span>عمولة المنصة: </span>
                    <span class="font-bold text-red-500">-{{ fmt(stats?.platform_cut) }} ج.م</span>
                </div>
            </div>

            <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                <div class="flex items-center justify-between text-xs text-stone-500 dark:text-stone-400 font-bold mb-2">
                    <span>الطلبات المكتملة</span>
                    <span class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600">
                        <CheckCircle2 class="w-4 h-4" />
                    </span>
                </div>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-3xl font-black text-stone-900 dark:text-white">
                        {{ stats?.delivered_orders ?? 0 }}
                    </span>
                    <span class="text-xs font-bold text-stone-400">
                        من أصل {{ stats?.total_orders ?? 0 }} طلب
                    </span>
                </div>
                <div class="flex items-center justify-between mt-2 pt-2 border-t border-stone-100 dark:border-stone-800 text-[11px]">
                    <span class="text-emerald-600 font-bold">
                        نسبة الإتمام: {{ stats?.completion_rate ?? 0 }}%
                    </span>
                    <span class="text-red-500 font-bold">
                        ملغي: {{ stats?.cancelled_orders ?? 0 }} ({{ stats?.cancellation_rate ?? 0 }}%)
                    </span>
                </div>
            </div>

            <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                <div class="flex items-center justify-between text-xs text-stone-500 dark:text-stone-400 font-bold mb-2">
                    <span>متوسط قيمة الطلب (AOV)</span>
                    <span class="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600">
                        <TrendingUp class="w-4 h-4" />
                    </span>
                </div>
                <div class="flex items-baseline gap-1.5 mt-1">
                    <span class="text-3xl font-black text-stone-900 dark:text-white">
                        {{ fmt(stats?.avg_order_value) }}
                    </span>
                    <span class="text-sm font-bold text-stone-400">ج.م</span>
                </div>
                <p class="text-[11px] text-stone-500 dark:text-stone-400 mt-2">
                    معدل دخل الطلب الواحد المكتمل
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-4 rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 flex items-center gap-3 shadow-xs">
                <div class="p-2.5 rounded-xl bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600">
                    <CheckCircle2 class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-xs text-stone-400 font-bold">تم التسليم بنجاح</p>
                    <p class="text-lg font-black text-stone-900 dark:text-white">{{ stats?.delivered_orders ?? 0 }}</p>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 flex items-center gap-3 shadow-xs">
                <div class="p-2.5 rounded-xl bg-amber-100 dark:bg-amber-950/50 text-amber-600">
                    <Clock class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-xs text-stone-400 font-bold">قيد التحضير / التجهيز</p>
                    <p class="text-lg font-black text-stone-900 dark:text-white">{{ stats?.preparing_orders ?? 0 }}</p>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 flex items-center gap-3 shadow-xs">
                <div class="p-2.5 rounded-xl bg-blue-100 dark:bg-blue-950/50 text-blue-600">
                    <ShoppingBag class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-xs text-stone-400 font-bold">طلبات جديدة معلقة</p>
                    <p class="text-lg font-black text-stone-900 dark:text-white">{{ stats?.pending_orders ?? 0 }}</p>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 flex items-center gap-3 shadow-xs">
                <div class="p-2.5 rounded-xl bg-red-100 dark:bg-red-950/50 text-red-600">
                    <XCircle class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-xs text-stone-400 font-bold">ملغية أو مرفوضة</p>
                    <p class="text-lg font-black text-stone-900 dark:text-white">{{ stats?.cancelled_orders ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-stone-100 dark:border-stone-800">
                <div>
                    <h2 class="text-lg font-black text-stone-900 dark:text-white flex items-center gap-2">
                        <Calendar class="w-5 h-5 text-orange-500" />
                        مقارنة الشهر الحالي بالشهر السابق
                    </h2>
                    <p class="text-xs text-stone-400 mt-1">
                        رصد نمو المبيعات وعدد الطلبات المسلّمة شهرياً
                    </p>
                </div>

                <div
                    v-if="momRevenueGrowth !== null"
                    :class="[
                        'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-bold text-xs',
                        momRevenueGrowth >= 0
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800'
                            : 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400 border border-red-200 dark:border-red-800',
                    ]"
                >
                    <ArrowUpRight v-if="momRevenueGrowth >= 0" class="w-4 h-4" />
                    <ArrowDownRight v-else class="w-4 h-4" />
                    <span>
                        نمو الإيراد:
                        {{ momRevenueGrowth >= 0 ? `+${momRevenueGrowth}%` : `${momRevenueGrowth}%` }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-5">
                <div class="p-5 rounded-2xl bg-orange-50/50 dark:bg-orange-950/10 border border-orange-200/60 dark:border-orange-900/30">
                    <span class="text-xs font-bold text-orange-600 dark:text-orange-400 uppercase">الشهر الحالي</span>
                    <div class="flex items-baseline gap-2 mt-2">
                        <span class="text-3xl font-black text-stone-900 dark:text-white">{{ fmt(stats?.this_month_revenue) }}</span>
                        <span class="text-sm font-bold text-stone-500">ج.م إيرادات</span>
                    </div>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">
                        {{ stats?.this_month_orders ?? 0 }} طلب مكتمل هذا الشهر
                    </p>
                </div>

                <div class="p-5 rounded-2xl bg-stone-50 dark:bg-stone-800/40 border border-stone-200 dark:border-stone-800">
                    <span class="text-xs font-bold text-stone-500 dark:text-stone-400 uppercase">الشهر السابق</span>
                    <div class="flex items-baseline gap-2 mt-2">
                        <span class="text-3xl font-black text-stone-900 dark:text-white">{{ fmt(stats?.last_month_revenue) }}</span>
                        <span class="text-sm font-bold text-stone-500">ج.م إيرادات</span>
                    </div>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">
                        {{ stats?.last_month_orders ?? 0 }} طلب مكتمل في الشهر الماضي
                    </p>
                </div>
            </div>
        </div>

        <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-lg font-black text-stone-900 dark:text-white">
                        نشاط المبيعات والطلبات اليومية (آخر 30 يوماً)
                    </h2>
                    <p class="text-xs text-stone-400 mt-0.5">
                        مرر على أي عمود لرؤية تفاصيل اليوم المحدد
                    </p>
                </div>

                <div class="flex items-center gap-1.5 p-1 bg-stone-100 dark:bg-stone-800 rounded-2xl text-xs font-bold">
                    <button
                        :class="[
                            'px-3 py-1.5 rounded-xl transition-all',
                            salesView === 'revenue'
                                ? 'bg-white dark:bg-stone-900 text-stone-900 dark:text-white shadow-xs'
                                : 'text-stone-500 hover:text-stone-900 dark:hover:text-white',
                        ]"
                        @click="salesView = 'revenue'"
                    >
                        الإيراد اليومي (ج.م)
                    </button>
                    <button
                        :class="[
                            'px-3 py-1.5 rounded-xl transition-all',
                            salesView === 'orders'
                                ? 'bg-white dark:bg-stone-900 text-stone-900 dark:text-white shadow-xs'
                                : 'text-stone-500 hover:text-stone-900 dark:hover:text-white',
                        ]"
                        @click="salesView = 'orders'"
                    >
                        عدد الطلبات
                    </button>
                </div>
            </div>

            <div v-if="daily_sales.length === 0" class="py-12 text-center text-stone-400 text-sm font-bold">
                لا توجد بيانات مبيعات في آخر 30 يوماً
            </div>
            <div v-else class="space-y-4">
                <div class="h-48 flex items-end gap-1.5 sm:gap-2 pt-4 px-2 border-b border-stone-100 dark:border-stone-800">
                    <div
                        v-for="(day, idx) in daily_sales"
                        :key="day.date || idx"
                        class="group relative flex-1 flex flex-col items-center justify-end h-full"
                    >
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none absolute -top-14 z-20 bg-stone-950 text-white text-[11px] font-bold px-3 py-1.5 rounded-xl whitespace-nowrap shadow-xl">
                            <p class="text-orange-400">{{ day.date }}</p>
                            <p>
                                <template v-if="salesView === 'revenue'">
                                    {{ fmt(day.revenue) }} ج.م ({{ day.delivered_count }} طلب)
                                </template>
                                <template v-else>
                                    {{ day.delivered_count }} مكتمل | {{ day.cancelled_count }} ملغي
                                </template>
                            </p>
                        </div>

                        <div
                            :style="{ height: `${barHeight(day)}%` }"
                            :class="[
                                'w-full rounded-t-lg transition-all duration-300',
                                (salesView === 'revenue' ? day.revenue : day.delivered_count) > 0
                                    ? salesView === 'revenue'
                                        ? 'bg-gradient-to-t from-orange-600 to-amber-400 group-hover:from-orange-500 group-hover:to-amber-300 shadow-xs'
                                        : 'bg-gradient-to-t from-emerald-600 to-teal-400 group-hover:from-emerald-500 group-hover:to-teal-300 shadow-xs'
                                    : 'bg-stone-100 dark:bg-stone-800',
                            ]"
                        />
                    </div>
                </div>

                <div class="flex justify-between text-[11px] text-stone-400 font-bold px-2">
                    <span>{{ daily_sales[0]?.date }}</span>
                    <span>{{ daily_sales[Math.floor(daily_sales.length / 2)]?.date }}</span>
                    <span>اليوم ({{ daily_sales[daily_sales.length - 1]?.date }})</span>
                </div>
            </div>
        </div>

        <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-black text-stone-900 dark:text-white">
                    الملخص المالي لآخر 6 أشهر
                </h2>
                <span class="text-xs text-stone-400 font-bold">
                    تراكمي شهري
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm">
                    <thead>
                        <tr class="border-b border-stone-100 dark:border-stone-800 text-stone-400 font-bold text-xs">
                            <th class="py-3 px-4">الشهر</th>
                            <th class="py-3 px-4">إجمالي المبيعات</th>
                            <th class="py-3 px-4">صافي ربح المطعم</th>
                            <th class="py-3 px-4">طلبات مكتملة</th>
                            <th class="py-3 px-4">طلبات ملغية</th>
                            <th class="py-3 px-4">نسبة الإنجاز</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800 font-medium">
                        <tr
                            v-for="(m, idx) in monthly_summary"
                            :key="idx"
                            class="hover:bg-stone-50 dark:hover:bg-stone-800/30 transition-colors"
                        >
                            <td class="py-3.5 px-4 font-bold text-stone-900 dark:text-white">
                                {{ m.month }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-stone-900 dark:text-white">
                                {{ fmt(m.revenue) }} <span class="text-xs font-normal text-stone-400">ج.م</span>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-emerald-600 dark:text-emerald-400">
                                {{ fmt(m.net_earnings) }} <span class="text-xs font-normal text-stone-400">ج.م</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center gap-1 text-emerald-600 font-bold">
                                    <CheckCircle2 class="w-3.5 h-3.5" />
                                    {{ m.delivered_orders }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center gap-1 text-red-500 font-bold">
                                    <XCircle class="w-3.5 h-3.5" />
                                    {{ m.cancelled_orders }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-bold">
                                <span
                                    :class="[
                                        'px-2.5 py-1 rounded-full text-xs',
                                        monthCompRate(m) >= 80
                                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400'
                                            : monthCompRate(m) >= 50
                                              ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400'
                                              : 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-400',
                                    ]"
                                >
                                    {{ monthCompRate(m) }}%
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-amber-500/10 text-amber-500">
                        <Award class="w-5 h-5" />
                    </span>
                    <div>
                        <h2 class="text-lg font-black text-stone-900 dark:text-white">
                            الأصناف الأكثر مبيعاً وتحقيقاً للإيراد
                        </h2>
                        <p class="text-xs text-stone-400">
                            أعلى 10 وجبات طلباً في مطعمك
                        </p>
                    </div>
                </div>
            </div>

            <div v-if="top_items.length === 0" class="py-8 text-center text-stone-400 text-sm font-bold">
                لا توجد أصناف مباعة بعد
            </div>
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="(item, idx) in top_items"
                    :key="idx"
                    class="p-4 rounded-2xl bg-stone-50 dark:bg-stone-800/40 border border-stone-200/70 dark:border-stone-800 flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <span
                            :class="[
                                'w-8 h-8 rounded-xl flex items-center justify-center font-black text-xs',
                                idx === 0
                                    ? 'bg-amber-500 text-white shadow-md shadow-amber-500/30'
                                    : idx === 1
                                      ? 'bg-stone-300 dark:bg-stone-700 text-stone-800 dark:text-stone-200'
                                      : idx === 2
                                        ? 'bg-amber-700/60 text-white'
                                        : 'bg-stone-200 dark:bg-stone-800 text-stone-600 dark:text-stone-400',
                            ]"
                        >
                            #{{ idx + 1 }}
                        </span>
                        <div>
                            <p class="font-bold text-stone-900 dark:text-white text-sm">
                                {{ item.name }}
                            </p>
                            <p class="text-xs text-stone-400">
                                تم طلب {{ item.qty }} مرة
                            </p>
                        </div>
                    </div>
                    <div class="text-left">
                        <p class="font-black text-emerald-600 dark:text-emerald-400 text-sm">
                            {{ fmt(item.revenue) }} ج.م
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
