<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, TrendingUp, TrendingDown, DollarSign, BarChart2 } from '@lucide/vue';
import { Bar } from 'vue-chartjs';
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
);

interface MonthlyPL {
    month: string;
    revenue: number;
    expenses: number;
    profit: number;
}

const props = defineProps<{
    monthlyData: MonthlyPL[];
    summary: {
        total_revenue: number;
        total_expenses: number;
        net_profit: number;
        profit_margin: number;
    };
}>();

const fmt = (v: number): string => (v / 100).toFixed(2);
const isProfit = computed(() => props.summary.net_profit >= 0);

const chartData = computed(() => ({
    labels: props.monthlyData.map((d) => d.month),
    datasets: [
        {
            type: 'bar' as const,
            label: 'الإيرادات',
            data: props.monthlyData.map((d) => d.revenue / 100),
            backgroundColor: 'rgba(249, 115, 22, 0.8)',
            borderRadius: 4,
            order: 2,
        },
        {
            type: 'bar' as const,
            label: 'المصروفات',
            data: props.monthlyData.map((d) => d.expenses / 100),
            backgroundColor: 'rgba(239, 68, 68, 0.7)',
            borderRadius: 4,
            order: 2,
        },
        {
            type: 'line' as const,
            label: 'صافي الربح',
            data: props.monthlyData.map((d) => d.profit / 100),
            borderColor: '#10b981',
            backgroundColor: '#10b981',
            borderWidth: 2.5,
            pointRadius: 4,
            pointBackgroundColor: '#10b981',
            tension: 0.3,
            order: 1,
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

const rowMargin = (row: MonthlyPL): number =>
    row.revenue > 0 ? (row.profit / row.revenue) * 100 : 0;
</script>

<template>
    <Head title="الربح والخسارة" />

    <div class="space-y-6" dir="rtl">
        <div class="flex items-center gap-4">
            <Link
                href="/admin/finance"
                class="p-2 rounded-lg bg-white hover:bg-stone-100 text-stone-400 hover:text-stone-900 transition-colors"
            >
                <ArrowLeft class="w-5 h-5" />
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-stone-900">بيان الربح والخسارة</h1>
                <p class="text-stone-400 text-sm mt-1">الأداء المالي الكلي للمنصة</p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-5">
                <DollarSign class="w-6 h-6 text-amber-400 mb-3" />
                <p class="text-2xl font-bold text-stone-900">{{ fmt(summary.total_revenue) }} ج</p>
                <p class="text-stone-400 text-xs mt-1">إجمالي الإيرادات</p>
            </div>
            <div class="bg-red-500/10 border border-red-500/20 rounded-2xl p-5">
                <TrendingDown class="w-6 h-6 text-red-400 mb-3" />
                <p class="text-2xl font-bold text-stone-900">{{ fmt(summary.total_expenses) }} ج</p>
                <p class="text-stone-400 text-xs mt-1">إجمالي المصروفات</p>
            </div>
            <div
                :class="[
                    isProfit ? 'bg-emerald-500/10 border-emerald-500/20' : 'bg-red-500/10 border-red-500/20',
                    'border rounded-2xl p-5',
                ]"
            >
                <TrendingUp v-if="isProfit" class="w-6 h-6 text-emerald-400 mb-3" />
                <TrendingDown v-else class="w-6 h-6 text-red-400 mb-3" />
                <p :class="['text-2xl font-bold', isProfit ? 'text-emerald-400' : 'text-red-400']">
                    {{ fmt(summary.net_profit) }} ج
                </p>
                <p class="text-stone-400 text-xs mt-1">صافي الربح</p>
            </div>
            <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-2xl p-5">
                <BarChart2 class="w-6 h-6 text-indigo-400 mb-3" />
                <p :class="['text-2xl font-bold', isProfit ? 'text-emerald-400' : 'text-red-400']">
                    {{ summary.profit_margin.toFixed(1) }}%
                </p>
                <p class="text-stone-400 text-xs mt-1">هامش الربح</p>
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
            <h2 class="text-lg font-semibold text-stone-900 mb-6">الأداء الشهري</h2>
            <div class="h-[350px]">
                <Bar :data="chartData" :options="chartOptions" />
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
            <h2 class="text-lg font-semibold text-stone-900 mb-4">التفاصيل الشهرية</h2>
            <div class="overflow-x-auto">
                <table class="record-cards w-full text-sm">
                    <thead>
                        <tr class="text-stone-400 border-b border-stone-200">
                            <th class="text-right pb-3 font-medium">الشهر</th>
                            <th class="text-right pb-3 font-medium">الإيرادات</th>
                            <th class="text-right pb-3 font-medium">المصروفات</th>
                            <th class="text-right pb-3 font-medium">صافي الربح</th>
                            <th class="text-right pb-3 font-medium">الهامش</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr
                            v-for="(row, i) in monthlyData"
                            :key="i"
                            class="hover:bg-white transition-colors"
                        >
                            <td data-label="الشهر" class="is-title py-3 text-stone-900 font-medium">{{ row.month }}</td>
                            <td data-label="الإيرادات" class="py-3 text-amber-400">{{ fmt(row.revenue) }} ج</td>
                            <td data-label="المصروفات" class="py-3 text-red-400">{{ fmt(row.expenses) }} ج</td>
                            <td data-label="صافي الربح"
                                :class="[
                                    'py-3 font-bold',
                                    row.profit >= 0 ? 'text-emerald-400' : 'text-red-400',
                                ]"
                            >
                                {{ fmt(row.profit) }} ج
                            </td>
                            <td data-label="الهامش" :class="['py-3', row.profit >= 0 ? 'text-emerald-400' : 'text-red-400']">
                                {{ rowMargin(row).toFixed(1) }}%
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
