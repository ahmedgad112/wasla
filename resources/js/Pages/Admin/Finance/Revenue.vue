<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, DollarSign, TrendingUp, TrendingDown, BarChart2 } from '@lucide/vue';
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
} from 'chart.js';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
);

const props = defineProps<{
    period: string;
    revenueByDay: Array<{ date: string; revenue: number; commission: number }>;
    revenueByRestaurant: Array<{
        restaurant_name: string;
        revenue: number;
        commission: number;
        orders: number;
    }>;
    totals: {
        total_revenue: number;
        total_commission: number;
        avg_daily: number;
        growth_pct: number;
    };
}>();

const fmt = (v: number): string => (v / 100).toFixed(2);

const cards = computed(() => [
    {
        label: 'إجمالي الإيرادات',
        value: `${fmt(props.totals.total_revenue)} ج`,
        icon: DollarSign,
        color: 'text-amber-400',
        bg: 'bg-amber-500/10',
    },
    {
        label: 'إجمالي العمولات',
        value: `${fmt(props.totals.total_commission)} ج`,
        icon: TrendingUp,
        color: 'text-orange-400',
        bg: 'bg-orange-500/10',
    },
    {
        label: 'متوسط يومي',
        value: `${fmt(props.totals.avg_daily)} ج`,
        icon: BarChart2,
        color: 'text-indigo-400',
        bg: 'bg-indigo-500/10',
    },
    {
        label: 'نسبة النمو',
        value: `${props.totals.growth_pct > 0 ? '+' : ''}${props.totals.growth_pct.toFixed(1)}%`,
        icon: props.totals.growth_pct >= 0 ? TrendingUp : TrendingDown,
        color: props.totals.growth_pct >= 0 ? 'text-emerald-400' : 'text-red-400',
        bg: 'bg-emerald-500/10',
    },
]);

const chartData = computed(() => ({
    labels: props.revenueByDay.map((d) => d.date),
    datasets: [
        {
            label: 'الإيرادات',
            data: props.revenueByDay.map((d) => d.revenue / 100),
            borderColor: '#f97316',
            backgroundColor: 'rgba(249, 115, 22, 0.25)',
            borderWidth: 2,
            fill: true,
            tension: 0.3,
            pointRadius: 0,
        },
        {
            label: 'العمولات',
            data: props.revenueByDay.map((d) => d.commission / 100),
            borderColor: '#818cf8',
            backgroundColor: 'rgba(129, 140, 248, 0.25)',
            borderWidth: 2,
            fill: true,
            tension: 0.3,
            pointRadius: 0,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: { color: '#78716c', font: { size: 11 } },
        },
        tooltip: {
            backgroundColor: '#1c1917',
            borderColor: '#44403c',
            borderWidth: 1,
            titleColor: '#fff',
            bodyColor: '#fff',
            callbacks: {
                label: (ctx: { dataset: { label?: string }; parsed: { y: number | null } }) =>
                    `${ctx.dataset.label ?? ''}: ${ctx.parsed.y ?? 0} ج`,
            },
        },
    },
    scales: {
        x: {
            ticks: { color: '#78716c', font: { size: 11 } },
            grid: { color: 'rgba(255,255,255,0.06)' },
        },
        y: {
            ticks: { color: '#78716c', font: { size: 11 } },
            grid: { color: 'rgba(255,255,255,0.06)' },
        },
    },
};
</script>

<template>
    <Head title="تفاصيل الإيرادات" />

    <div class="space-y-6" dir="rtl">
        <div class="flex items-center gap-4">
            <Link
                href="/admin/finance"
                class="p-2 rounded-lg bg-white hover:bg-stone-100 text-stone-400 hover:text-stone-900 transition-colors"
            >
                <ArrowLeft class="w-5 h-5" />
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-stone-900">تفاصيل الإيرادات</h1>
                <p class="text-stone-400 text-sm mt-1">تحليل الإيرادات والعمولات</p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
                v-for="(card, i) in cards"
                :key="i"
                :class="[card.bg, 'border border-stone-200 rounded-2xl p-5']"
            >
                <component :is="card.icon" :class="['w-6 h-6 mb-3', card.color]" />
                <p class="text-2xl font-bold text-stone-900">{{ card.value }}</p>
                <p class="text-stone-400 text-xs mt-1">{{ card.label }}</p>
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
            <h2 class="text-lg font-semibold text-stone-900 mb-6">الإيرادات اليومية</h2>
            <div class="h-[300px]">
                <Line :data="chartData" :options="chartOptions" />
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
            <h2 class="text-lg font-semibold text-stone-900 mb-4">الإيرادات حسب المطعم</h2>
            <div class="overflow-x-auto">
                <table class="record-cards w-full text-sm">
                    <thead>
                        <tr class="text-stone-400 border-b border-stone-200">
                            <th class="text-right pb-3 font-medium">المطعم</th>
                            <th class="text-right pb-3 font-medium">الطلبات</th>
                            <th class="text-right pb-3 font-medium">الإيرادات</th>
                            <th class="text-right pb-3 font-medium">العمولة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr
                            v-for="(r, i) in revenueByRestaurant"
                            :key="i"
                            class="hover:bg-white transition-colors"
                        >
                            <td data-label="المطعم" class="is-title py-3 text-stone-900 font-medium">{{ r.restaurant_name }}</td>
                            <td data-label="الطلبات" class="py-3 text-stone-300">{{ r.orders }}</td>
                            <td data-label="الإيرادات" class="py-3 text-amber-400 font-semibold">{{ fmt(r.revenue) }} ج</td>
                            <td data-label="العمولة" class="py-3 text-indigo-400 font-semibold">{{ fmt(r.commission) }} ج</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
