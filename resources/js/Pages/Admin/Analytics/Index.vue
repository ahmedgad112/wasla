<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { TrendingUp, ShoppingBag, Users, Store, Clock, Star } from '@lucide/vue';
import { Bar, Line, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    Filler,
    ArcElement,
} from 'chart.js';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    Filler,
    ArcElement,
);

const props = defineProps<{
    ordersByHour: Array<{ hour: number; count: number }>;
    ordersByDay: Array<{ day: string; count: number; revenue: number }>;
    topRestaurants: Array<{ name: string; orders: number; revenue: number }>;
    topMenuItems: Array<{ name: string; restaurant: string; count: number }>;
    customerRetention: { new_customers: number; returning_customers: number };
    avgDeliveryTime: number;
    summaryStats: {
        total_orders_30d: number;
        revenue_30d: number;
        new_customers_30d: number;
        avg_rating: number;
    };
}>();

const fmt = (v: number): string => Number(v || 0).toFixed(2);

const tooltipStyle = {
    backgroundColor: '#1c1917',
    borderColor: '#44403c',
    borderWidth: 1,
    titleColor: '#fff',
    bodyColor: '#fff',
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: { ...tooltipStyle },
    },
    scales: {
        x: {
            ticks: { color: '#78716c', font: { size: 10 } },
            grid: { color: 'rgba(255,255,255,0.06)' },
        },
        y: {
            ticks: { color: '#78716c', font: { size: 10 } },
            grid: { color: 'rgba(255,255,255,0.06)' },
        },
    },
};

const ordersByDayData = computed(() => ({
    labels: props.ordersByDay.map((d) => d.day),
    datasets: [
        {
            label: 'الطلبات',
            data: props.ordersByDay.map((d) => d.count),
            backgroundColor: '#f97316',
            borderRadius: 4,
        },
    ],
}));

const ordersByHourData = computed(() => ({
    labels: props.ordersByHour.map((d) => `${d.hour}:00`),
    datasets: [
        {
            label: 'الطلبات',
            data: props.ordersByHour.map((d) => d.count),
            borderColor: '#818cf8',
            backgroundColor: 'rgba(129, 140, 248, 0.15)',
            borderWidth: 2,
            pointRadius: 0,
            tension: 0.3,
            fill: true,
        },
    ],
}));

const retentionData = computed(() => ({
    labels: ['جدد', 'عائدون'],
    datasets: [
        {
            data: [props.customerRetention.new_customers, props.customerRetention.returning_customers],
            backgroundColor: ['#f97316', '#818cf8'],
            borderWidth: 0,
        },
    ],
}));

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '68%',
    plugins: {
        legend: { display: false },
        tooltip: { ...tooltipStyle },
    },
};

const kpis = computed(() => [
    {
        label: 'الطلبات (30 يوم)',
        value: props.summaryStats.total_orders_30d,
        icon: ShoppingBag,
        color: 'text-orange-400',
    },
    {
        label: 'الإيرادات (30 يوم)',
        value: `${fmt(props.summaryStats.revenue_30d)} ج`,
        icon: TrendingUp,
        color: 'text-amber-400',
    },
    {
        label: 'عملاء جدد',
        value: props.summaryStats.new_customers_30d,
        icon: Users,
        color: 'text-indigo-400',
    },
    {
        label: 'متوسط التوصيل',
        value: `${props.avgDeliveryTime} د`,
        icon: Clock,
        color: 'text-purple-400',
    },
]);

const restaurantBarWidth = (orders: number): string => {
    const max = props.topRestaurants[0]?.orders || 1;
    return `${Math.min(100, (orders / max) * 100)}%`;
};
</script>

<template>
    <Head title="التحليلات" />

    <div class="space-y-6" dir="rtl">
        <div>
            <h1 class="text-2xl font-bold text-stone-900">التحليلات والإحصاءات</h1>
            <p class="text-stone-400 text-sm mt-1">أداء المنصة خلال آخر 30 يوماً</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
                v-for="(kpi, i) in kpis"
                :key="i"
                class="bg-white border border-stone-200 rounded-xl p-5"
            >
                <component :is="kpi.icon" :class="['w-5 h-5 mb-3', kpi.color]" />
                <p class="text-2xl font-bold text-stone-900">{{ kpi.value }}</p>
                <p class="text-xs text-stone-400 mt-1">{{ kpi.label }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 mb-4">الطلبات اليومية</h2>
                <div class="h-[250px]">
                    <Bar :data="ordersByDayData" :options="chartOptions" />
                </div>
            </div>

            <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 mb-4">الطلبات حسب الساعة</h2>
                <div class="h-[250px]">
                    <Line :data="ordersByHourData" :options="chartOptions" />
                </div>
            </div>

            <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 mb-4">العملاء الجدد مقابل العائدين</h2>
                <div class="flex items-center gap-6">
                    <div class="w-[200px] h-[200px]">
                        <Doughnut :data="retentionData" :options="doughnutOptions" />
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-orange-500" />
                            <div>
                                <p class="text-stone-900 font-semibold">{{ customerRetention.new_customers }}</p>
                                <p class="text-stone-400 text-xs">عملاء جدد</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-indigo-500" />
                            <div>
                                <p class="text-stone-900 font-semibold">{{ customerRetention.returning_customers }}</p>
                                <p class="text-stone-400 text-xs">عملاء عائدون</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 mb-4 flex items-center gap-2">
                    <Store class="w-5 h-5 text-orange-400" />
                    أفضل المطاعم
                </h2>
                <div class="space-y-3">
                    <div
                        v-for="(r, i) in topRestaurants"
                        :key="i"
                        class="flex items-center gap-3"
                    >
                        <span class="text-stone-500 text-sm w-5">{{ i + 1 }}</span>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-stone-900 text-sm font-medium">{{ r.name }}</p>
                                <p class="text-stone-300 text-xs">{{ r.orders }} طلب</p>
                            </div>
                            <div class="h-1.5 bg-white/10 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-orange-500 rounded-full transition-all"
                                    :style="{ width: restaurantBarWidth(r.orders) }"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
            <h2 class="text-lg font-semibold text-stone-900 mb-4 flex items-center gap-2">
                <Star class="w-5 h-5 text-amber-400" />
                أكثر الأطباق طلباً
            </h2>
            <div class="overflow-x-auto">
                <table class="record-cards w-full text-sm">
                    <thead>
                        <tr class="text-stone-400 border-b border-stone-200">
                            <th class="text-right pb-3 font-medium">#</th>
                            <th class="text-right pb-3 font-medium">الطبق</th>
                            <th class="text-right pb-3 font-medium">المطعم</th>
                            <th class="text-right pb-3 font-medium">عدد الطلبات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr
                            v-for="(item, i) in topMenuItems"
                            :key="i"
                            class="hover:bg-white transition-colors"
                        >
                            <td data-label="#" class="py-3 text-stone-500">{{ i + 1 }}</td>
                            <td data-label="الطبق" class="is-title py-3 text-stone-900 font-medium">{{ item.name }}</td>
                            <td data-label="المطعم" class="py-3 text-stone-400">{{ item.restaurant }}</td>
                            <td data-label="عدد الطلبات" class="py-3">
                                <span class="px-2 py-1 bg-orange-500/20 text-orange-400 rounded text-xs font-semibold">
                                    {{ item.count }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
