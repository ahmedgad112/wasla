<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    Bike,
    TrendingUp,
    ShoppingBag,
    Award,
    Phone,
    CheckCircle2,
    Flame,
    BarChart2,
    Search,
    Activity,
} from '@lucide/vue';
import { Bar, Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    Filler,
    Title,
    Tooltip,
    Legend,
} from 'chart.js';

ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    LineElement,
    PointElement,
    Filler,
    Title,
    Tooltip,
    Legend,
);

interface DriverStat {
    id: number;
    name: string;
    phone: string;
    is_active: boolean;
    availability_status: 'AVAILABLE' | 'BUSY' | 'OFFLINE';
    total_orders: number;
    today_orders: number;
    active_orders: number;
    today_earnings: number;
    total_earnings: number;
    last_order_at: string | null;
    daily: {
        date: string;
        orders: number;
    }[];
}

const props = withDefaults(
    defineProps<{
        driver_stats?: DriverStat[];
        summary: {
            total_drivers: number;
            available_drivers: number;
            busy_drivers: number;
            today_delivered_orders: number;
            today_total_amount: number;
            total_delivered_orders: number;
            top_driver: DriverStat | null;
        };
        comparison_chart?: {
            name: string;
            total_orders: number;
            today_orders: number;
            earnings: number;
        }[];
        timeline_chart?: {
            date: string;
            day: string;
            orders: number;
        }[];
        restaurant: {
            id: number;
            name: string;
        };
    }>(),
    {
        driver_stats: () => [],
        comparison_chart: () => [],
        timeline_chart: () => [],
    },
);

const searchTerm = ref('');
const statusFilter = ref<'ALL' | 'AVAILABLE' | 'BUSY' | 'OFFLINE'>('ALL');

const filteredDrivers = computed(() =>
    props.driver_stats.filter((d) => {
        const matchesSearch =
            d.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            d.phone.includes(searchTerm.value);
        const matchesStatus = statusFilter.value === 'ALL' || d.availability_status === statusFilter.value;
        return matchesSearch && matchesStatus;
    }),
);

const maxOrders = computed(() => Math.max(...props.driver_stats.map((d) => d.total_orders), 1));

const comparisonData = computed(() => ({
    labels: props.comparison_chart.map((d) => d.name),
    datasets: [
        {
            label: 'إجمالي الطلبات',
            data: props.comparison_chart.map((d) => d.total_orders),
            backgroundColor: '#ea580c',
            borderRadius: 6,
        },
        {
            label: 'طلبات اليوم',
            data: props.comparison_chart.map((d) => d.today_orders),
            backgroundColor: '#10b981',
            borderRadius: 6,
        },
    ],
}));

const timelineData = computed(() => ({
    labels: props.timeline_chart.map((d) => d.date),
    datasets: [
        {
            label: 'تم توصيله',
            data: props.timeline_chart.map((d) => d.orders),
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.25)',
            fill: true,
            tension: 0.35,
            borderWidth: 2.5,
            pointRadius: 3,
            pointBackgroundColor: '#10b981',
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            labels: { font: { size: 12 }, color: '#78716c' },
        },
        tooltip: {
            backgroundColor: '#1c1917',
            borderColor: '#292524',
            borderWidth: 1,
            titleColor: '#fff',
            bodyColor: '#fff',
            titleFont: { size: 12 },
            bodyFont: { size: 12 },
        },
    },
    scales: {
        x: {
            ticks: { color: '#78716c', font: { size: 11 } },
            grid: { color: 'rgba(0,0,0,0.06)' },
        },
        y: {
            ticks: { color: '#78716c', font: { size: 11 } },
            grid: { color: 'rgba(0,0,0,0.06)' },
            beginAtZero: true,
        },
    },
};

const timelineOptions = {
    ...chartOptions,
    plugins: {
        ...chartOptions.plugins,
        legend: { display: false },
        tooltip: {
            ...chartOptions.plugins.tooltip,
            callbacks: {
                title: (items: { dataIndex: number }[]) => {
                    const idx = items[0]?.dataIndex ?? 0;
                    const row = props.timeline_chart[idx];
                    return row ? `${row.day || ''} (${row.date})`.trim() : '';
                },
                label: (ctx: { raw: unknown }) => `${ctx.raw} طلب`,
            },
        },
    },
};
</script>

<template>
    <Head title="إحصائيات الكباتن — إدارة المطعم" />

    <div class="space-y-6 pb-12" dir="rtl">
        <div class="relative overflow-hidden p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-orange-600 via-amber-600 to-orange-500 text-white shadow-xl">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-bold mb-3">
                        <Activity class="w-3.5 h-3.5 text-amber-200" />
                        <span>تقرير حركة وأداء مناديب الفرع</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black">
                        سجل وإحصائيات كباتن التوصيل
                    </h1>
                    <p class="text-orange-100 text-xs sm:text-sm mt-1 max-w-xl">
                        متابعة دقيقة لجميع الطلبات التي أخذها كل كابتن، المبالغ المحصلة كاش، ومَن هو الأكثر تفاعلاً وإنجازاً اليوم وخلال الشهر.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        href="/restaurant/delivery-drivers"
                        class="px-4 py-2.5 rounded-2xl bg-white text-orange-600 hover:bg-orange-50 font-black text-xs shadow-md transition flex items-center gap-2"
                    >
                        <Bike class="w-4 h-4" />
                        <span>إدارة الكباتن</span>
                    </Link>
                    <Link
                        href="/restaurant/orders"
                        class="px-4 py-2.5 rounded-2xl bg-white/10 hover:bg-white/20 text-stone-900 font-bold text-xs backdrop-blur-md transition flex items-center gap-2"
                    >
                        <ShoppingBag class="w-4 h-4" />
                        <span>الطلبات الواردة</span>
                    </Link>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-5 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                <div class="flex items-center justify-between text-xs text-stone-400 font-bold mb-2">
                    <span>توصيلات اليوم المكتملة</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <CheckCircle2 class="w-4 h-4" />
                    </div>
                </div>
                <p class="text-2xl font-black text-stone-900 dark:text-white">
                    {{ summary?.today_delivered_orders || 0 }}
                    <span class="text-xs font-normal text-stone-400 mr-1.5">طلب</span>
                </p>
                <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium mt-1">
                    تحصيل اليوم: {{ Number(summary?.today_total_amount || 0).toLocaleString() }} ج.م
                </p>
            </div>

            <div class="p-5 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                <div class="flex items-center justify-between text-xs text-stone-400 font-bold mb-2">
                    <span>إجمالي كل التوصيلات</span>
                    <div class="w-8 h-8 rounded-xl bg-orange-500/10 text-orange-600 dark:text-orange-400 flex items-center justify-center">
                        <ShoppingBag class="w-4 h-4" />
                    </div>
                </div>
                <p class="text-2xl font-black text-stone-900 dark:text-white">
                    {{ summary?.total_delivered_orders || 0 }}
                    <span class="text-xs font-normal text-stone-400 mr-1.5">رحلة ناجحة</span>
                </p>
                <p class="text-[11px] text-stone-500 mt-1">
                    عبر جميع كباتن الفرع
                </p>
            </div>

            <div class="p-5 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                <div class="flex items-center justify-between text-xs text-stone-400 font-bold mb-2">
                    <span>الكباتن الجاهزين الآن</span>
                    <div class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <Bike class="w-4 h-4" />
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <p class="text-2xl font-black text-stone-900 dark:text-white">
                        {{ summary?.available_drivers || 0 }}
                    </p>
                    <span class="text-xs text-stone-400">من إجمالي {{ summary?.total_drivers || 0 }}</span>
                </div>
                <p class="text-[11px] text-stone-500 mt-1">
                    {{ summary?.busy_drivers || 0 }} كابتن في طريق التوصيل حالياً
                </p>
            </div>

            <div class="p-5 rounded-3xl bg-gradient-to-br from-amber-500/10 via-orange-500/5 to-transparent dark:from-amber-950/30 border border-amber-300 dark:border-amber-800/60 shadow-xs">
                <div class="flex items-center justify-between text-xs text-amber-700 dark:text-amber-400 font-bold mb-2">
                    <span class="flex items-center gap-1">
                        <Award class="w-3.5 h-3.5 text-amber-500" />
                        الكابتن الأكثر تفاعلاً
                    </span>
                    <Flame class="w-4 h-4 text-orange-500" />
                </div>
                <p class="text-lg font-black text-stone-900 dark:text-white truncate">
                    {{ summary?.top_driver?.name || 'لا يوجد بعد' }}
                </p>
                <p class="text-[11px] text-amber-700 dark:text-amber-300 font-bold mt-1">
                    {{ summary?.top_driver?.total_orders || 0 }} طلب مكتمل ({{ summary?.top_driver?.today_orders || 0 }} اليوم)
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-2">
                            <BarChart2 class="w-4 h-4 text-orange-500" />
                            <span>مقارنة إنجاز الكباتن (الأكثر طلباً)</span>
                        </h2>
                        <p class="text-xs text-stone-400 mt-0.5">
                            إجمالي الطلبات المكتملة وطلبات اليوم لكل كابتن
                        </p>
                    </div>
                </div>

                <div v-if="comparison_chart.length === 0" class="h-64 flex flex-col items-center justify-center text-stone-400 text-xs">
                    <Bike class="w-10 h-10 mb-2 stroke-1" />
                    <span>لا توجد بيانات كافية للكباتن حتى الآن</span>
                </div>
                <div v-else class="h-64 w-full">
                    <Bar :data="comparisonData" :options="chartOptions" />
                </div>
            </div>

            <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-2">
                            <TrendingUp class="w-4 h-4 text-emerald-500" />
                            <span>حركة التوصيل اليومية (آخر 14 يوماً)</span>
                        </h2>
                        <p class="text-xs text-stone-400 mt-0.5">
                            معدل الطلبات المسلمة يومياً من المطعم
                        </p>
                    </div>
                </div>

                <div v-if="timeline_chart.length === 0" class="h-64 flex flex-col items-center justify-center text-stone-400 text-xs">
                    <TrendingUp class="w-10 h-10 mb-2 stroke-1" />
                    <span>لا توجد بيانات كافية خلال الفترة</span>
                </div>
                <div v-else class="h-64 w-full">
                    <Line :data="timelineData" :options="timelineOptions" />
                </div>
            </div>
        </div>

        <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-stone-100 dark:border-stone-800">
                <div>
                    <h2 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-2">
                        <Bike class="w-5 h-5 text-orange-600" />
                        <span>جدول كباتن التوصيل وأرقام الأداء</span>
                    </h2>
                    <p class="text-xs text-stone-400 mt-0.5">
                        عرض تفصيلي لعدد الطلبات التي أخذها كل كابتن ومبالغ التحصيل
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <div class="relative">
                        <Search class="w-4 h-4 text-stone-400 absolute right-3 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="searchTerm"
                            type="text"
                            placeholder="بحث بالاسم أو الهاتف..."
                            class="pr-9 pl-3 py-1.5 rounded-xl border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 text-xs text-stone-800 dark:text-stone-200 focus:outline-hidden focus:ring-2 focus:ring-orange-500"
                        />
                    </div>

                    <select
                        v-model="statusFilter"
                        class="py-1.5 px-3 rounded-xl border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 text-xs text-stone-800 dark:text-stone-200 focus:outline-hidden focus:ring-2 focus:ring-orange-500 font-bold"
                    >
                        <option value="ALL">كل الحالات</option>
                        <option value="AVAILABLE">متاح وجاهز</option>
                        <option value="BUSY">مشغول بتوصيل</option>
                        <option value="OFFLINE">غير متصل</option>
                    </select>
                </div>
            </div>

            <div v-if="filteredDrivers.length === 0" class="text-center py-12">
                <Bike class="w-12 h-12 text-stone-300 dark:text-stone-600 mx-auto mb-2" />
                <p class="text-xs text-stone-400">لا يوجد كباتن مطابقين للبحث حالياً.</p>
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="border-b border-stone-100 dark:border-stone-800 text-stone-400 font-bold">
                            <th class="py-3 px-4">الكابتن</th>
                            <th class="py-3 px-3">الحالة الحالية</th>
                            <th class="py-3 px-3">طلبات اليوم</th>
                            <th class="py-3 px-3">الطلبات الجارية الآن</th>
                            <th class="py-3 px-3">إجمالي الطلبات</th>
                            <th class="py-3 px-3">كاش محصل اليوم</th>
                            <th class="py-3 px-3">إجمالي الكاش</th>
                            <th class="py-3 px-3">آخر توصيل</th>
                            <th class="py-3 px-4">مؤشر النشاط</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        <tr
                            v-for="(driver, index) in filteredDrivers"
                            :key="driver.id"
                            class="hover:bg-stone-50/70 dark:hover:bg-stone-800/40 transition"
                        >
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-2xl bg-orange-100 dark:bg-stone-800 text-orange-600 dark:text-orange-400 flex items-center justify-center font-black text-sm">
                                        <Award
                                            v-if="index === 0 && driver.total_orders > 0"
                                            class="w-5 h-5 text-amber-500"
                                        />
                                        <template v-else>{{ driver.name.charAt(0) }}</template>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-black text-stone-900 dark:text-white">
                                                {{ driver.name }}
                                            </span>
                                            <span
                                                v-if="index === 0 && driver.total_orders > 0"
                                                class="px-1.5 py-0.5 rounded-md bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 text-[10px] font-bold"
                                            >
                                                الأول
                                            </span>
                                        </div>
                                        <a
                                            :href="`tel:${driver.phone}`"
                                            class="text-[11px] text-stone-400 hover:text-orange-600 flex items-center gap-1 mt-0.5"
                                        >
                                            <Phone class="w-3 h-3" />
                                            <span>{{ driver.phone }}</span>
                                        </a>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3 px-3">
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold',
                                        driver.availability_status === 'AVAILABLE'
                                            ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300'
                                            : driver.availability_status === 'BUSY'
                                              ? 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300'
                                              : 'bg-stone-100 dark:bg-stone-800 text-stone-500',
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'w-1.5 h-1.5 rounded-full',
                                            driver.availability_status === 'AVAILABLE'
                                                ? 'bg-emerald-500'
                                                : driver.availability_status === 'BUSY'
                                                  ? 'bg-amber-500'
                                                  : 'bg-stone-400',
                                        ]"
                                    />
                                    {{
                                        driver.availability_status === 'AVAILABLE'
                                            ? 'جاهز للاستلام'
                                            : driver.availability_status === 'BUSY'
                                              ? 'في الطريق'
                                              : 'غير متصل'
                                    }}
                                </span>
                            </td>

                            <td class="py-3 px-3">
                                <span class="font-extrabold text-sm text-emerald-600 dark:text-emerald-400">
                                    {{ driver.today_orders }}
                                </span>
                                <span class="text-[10px] text-stone-400 mr-1">طلب</span>
                            </td>

                            <td class="py-3 px-3">
                                <span
                                    v-if="driver.active_orders > 0"
                                    class="px-2 py-0.5 rounded-full bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 font-bold text-xs"
                                >
                                    {{ driver.active_orders }} جاري الآن
                                </span>
                                <span v-else class="text-stone-400 text-[11px]">-</span>
                            </td>

                            <td class="py-3 px-3 font-black text-stone-900 dark:text-white">
                                {{ driver.total_orders }}
                            </td>

                            <td class="py-3 px-3 font-bold text-emerald-600 dark:text-emerald-400">
                                {{ Number(driver.today_earnings).toLocaleString() }} ج.م
                            </td>

                            <td class="py-3 px-3 font-bold text-stone-700 dark:text-stone-300">
                                {{ Number(driver.total_earnings).toLocaleString() }} ج.م
                            </td>

                            <td class="py-3 px-3 text-stone-400 text-[11px]">
                                {{ driver.last_order_at || 'لم يوصل بعد' }}
                            </td>

                            <td class="py-3 px-4 min-w-[140px]">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-stone-100 dark:bg-stone-800 rounded-full h-2 overflow-hidden">
                                        <div
                                            class="bg-gradient-to-r from-orange-500 to-amber-500 h-full rounded-full transition-all duration-500"
                                            :style="{ width: `${Math.round((driver.total_orders / maxOrders) * 100)}%` }"
                                        />
                                    </div>
                                    <span class="text-[10px] font-bold text-stone-400 w-8 text-left">
                                        {{ Math.round((driver.total_orders / maxOrders) * 100) }}%
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
