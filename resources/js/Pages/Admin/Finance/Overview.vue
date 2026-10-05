<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    DollarSign,
    TrendingUp,
    TrendingDown,
    Store,
    ArrowUpRight,
    CreditCard,
    ChevronDown,
    ChevronUp,
    Receipt,
    BarChart3,
    AlertTriangle,
    Search,
    FileSpreadsheet,
    Sparkles,
    ExternalLink,
    Check,
    CheckCircle2,
} from '@lucide/vue';
import { subscriptionPlanLabel } from '../../../lib/subscriptionPlans';

interface PlatformProfit {
    subscription_revenue: number;
    commission_revenue: number;
    total_platform_earn: number;
    total_expenses: number;
    net_profit: number;
    outstanding: number;
}

interface RestaurantProfit {
    id: number;
    name: string;
    slug: string;
    status: string;
    commission_type: string;
    commission_rate: number;
    subscription_fee: number;
    billing_cycle?: string;
    subscription_ends_at?: string | null;
    payment_due_date?: string | null;
    total_orders: number;
    delivered_orders: number;
    cancelled_orders: number;
    pending_orders: number;
    gross_revenue: number;
    delivery_fees: number;
    platform_cut: number;
    net_restaurant_earn: number;
    unpaid_due?: number;
    paid_amount?: number;
    overdue_count?: number;
}

interface MonthlyPnl {
    month: string;
    revenue: number;
    expenses: number;
    profit: number;
}

const props = withDefaults(
    defineProps<{
        summary: Record<string, number>;
        platform_profit: PlatformProfit;
        restaurant_profits?: RestaurantProfit[];
        monthly_pnl?: MonthlyPnl[];
        total_gmv?: number;
        filters: { start_date?: string; end_date?: string };
    }>(),
    {
        restaurant_profits: () => [],
        monthly_pnl: () => [],
        total_gmv: 0,
    },
);

const expandedRow = ref<number | null>(null);
const searchQuery = ref('');
const statusFilter = ref<'ALL' | 'DUE' | 'PAID' | 'ACTIVE' | 'SUSPENDED'>('ALL');

const fmt = (v: number | string | null | undefined): string => {
    const n = Number(v ?? 0);
    if (isNaN(n)) {
        return '0';
    }
    return Math.round(n).toLocaleString('en');
};

const totalRestaurantGross = computed(() =>
    props.restaurant_profits.reduce((s, r) => s + r.gross_revenue, 0),
);
const totalRestaurantNet = computed(() =>
    props.restaurant_profits.reduce((s, r) => s + r.net_restaurant_earn, 0),
);
const totalPlatformCut = computed(() =>
    props.restaurant_profits.reduce((s, r) => s + r.platform_cut, 0),
);
const totalDelivered = computed(() =>
    props.restaurant_profits.reduce((s, r) => s + r.delivered_orders, 0),
);
const totalCancelled = computed(() =>
    props.restaurant_profits.reduce((s, r) => s + r.cancelled_orders, 0),
);
const totalUnpaidDue = computed(() =>
    props.restaurant_profits.reduce((s, r) => s + (r.unpaid_due || 0), 0),
);

const dueCount = computed(
    () => props.restaurant_profits.filter((r) => (r.unpaid_due || 0) > 0).length,
);
const paidCount = computed(
    () => props.restaurant_profits.filter((r) => (r.unpaid_due || 0) === 0).length,
);

const filteredRestaurants = computed(() =>
    props.restaurant_profits.filter((r) => {
        const matchesSearch = r.name.toLowerCase().includes(searchQuery.value.toLowerCase().trim());
        if (!matchesSearch) {
            return false;
        }
        if (statusFilter.value === 'DUE') {
            return (r.unpaid_due || 0) > 0;
        }
        if (statusFilter.value === 'PAID') {
            return (r.unpaid_due || 0) === 0;
        }
        if (statusFilter.value === 'ACTIVE') {
            return r.status === 'ACTIVE';
        }
        if (statusFilter.value === 'SUSPENDED') {
            return r.status === 'SUSPENDED';
        }
        return true;
    }),
);

const reversedMonthly = computed(() => [...props.monthly_pnl].reverse());

const maxMonthlyRevenue = computed(() =>
    Math.max(...props.monthly_pnl.map((x) => x.revenue), 1),
);

const toggleExpand = (id: number): void => {
    expandedRow.value = expandedRow.value === id ? null : id;
};

const completionRate = (r: RestaurantProfit): number =>
    r.total_orders > 0 ? Math.round((r.delivered_orders / r.total_orders) * 100) : 0;

const cancelRate = (r: RestaurantProfit): number =>
    r.total_orders > 0 ? Math.round((r.cancelled_orders / r.total_orders) * 100) : 0;

const aov = (r: RestaurantProfit): string =>
    r.delivered_orders > 0 ? fmt(r.gross_revenue / r.delivered_orders) : '0';

const footerDelivered = computed(() =>
    filteredRestaurants.value.reduce((s, r) => s + r.delivered_orders, 0),
);
const footerCancelled = computed(() =>
    filteredRestaurants.value.reduce((s, r) => s + r.cancelled_orders, 0),
);
const footerGross = computed(() =>
    filteredRestaurants.value.reduce((s, r) => s + r.gross_revenue, 0),
);
const footerPlatform = computed(() =>
    filteredRestaurants.value.reduce((s, r) => s + r.platform_cut, 0),
);
const footerPaid = computed(() =>
    filteredRestaurants.value.reduce((s, r) => s + (r.paid_amount || 0), 0),
);
const footerUnpaid = computed(() =>
    filteredRestaurants.value.reduce((s, r) => s + (r.unpaid_due || 0), 0),
);
const footerNet = computed(() =>
    filteredRestaurants.value.reduce((s, r) => s + r.net_restaurant_earn, 0),
);
</script>

<template>
    <Head title="التقرير المالي وكشف حساب المطاعم — الإدارة المركزية" />

    <div class="space-y-10 max-w-7xl mx-auto pb-12">
        <div
            class="relative overflow-hidden p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-orange-600 via-amber-600 to-yellow-500 text-white shadow-xl shadow-orange-500/20"
        >
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                <div>
                    <div
                        class="flex items-center gap-2 text-xs font-black uppercase tracking-wider bg-white/20 backdrop-blur-md px-3 py-1 rounded-full w-fit mb-3"
                    >
                        <Sparkles class="w-3.5 h-3.5 text-yellow-200" />
                        إجمالي حجم المعاملات عبر المنصة (GMV)
                    </div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl sm:text-5xl font-black">{{ fmt(total_gmv) }}</span>
                        <span class="text-xl font-bold opacity-80">ج.م</span>
                    </div>
                    <p class="text-xs opacity-80 mt-1.5 font-medium">
                        القيمة النقدية الإجمالية لكافة الطلبات المنفذة والمسلمة بنجاح من جميع المطاعم
                    </p>
                </div>

                <div
                    class="grid grid-cols-3 gap-3 sm:gap-6 bg-black/15 backdrop-blur-md p-4 rounded-2xl border border-stone-200 text-center"
                >
                    <div class="px-2">
                        <p class="text-2xl sm:text-3xl font-black text-stone-900">{{ totalDelivered }}</p>
                        <p class="text-[11px] opacity-80 mt-0.5">طلب مكتمل</p>
                    </div>
                    <div class="px-2 border-r border-l border-stone-200">
                        <p class="text-2xl sm:text-3xl font-black text-yellow-200">{{ totalCancelled }}</p>
                        <p class="text-[11px] opacity-80 mt-0.5">طلب ملغي</p>
                    </div>
                    <div class="px-2">
                        <p class="text-2xl sm:text-3xl font-black text-stone-900">
                            {{ restaurant_profits.length }}
                        </p>
                        <p class="text-[11px] opacity-80 mt-0.5">مطعم شريك</p>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-2xl bg-emerald-500/10 text-emerald-600">
                        <DollarSign class="w-5 h-5" />
                    </span>
                    <div>
                        <h2 class="text-lg font-black text-stone-900 dark:text-white">أرباح منصتنا المركزية</h2>
                        <p class="text-xs text-stone-400">إيرادات اشتراكات المطاعم الشهرية + العمولات المقتطعة</p>
                    </div>
                </div>
                <span
                    class="text-xs font-black text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 px-3 py-1.5 rounded-xl border border-emerald-200 dark:border-emerald-800"
                >
                    صافي الربح: {{ fmt(platform_profit?.net_profit) }} ج.م
                </span>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    class="p-5 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs"
                >
                    <div class="flex items-center justify-between text-xs text-stone-400 font-bold mb-2">
                        <span>إيرادات الاشتراكات</span>
                        <CreditCard class="w-4 h-4 text-orange-500" />
                    </div>
                    <p class="text-2xl font-black text-stone-900 dark:text-white">
                        {{ fmt(platform_profit?.subscription_revenue) }}
                        <span class="text-xs font-normal text-stone-400">ج.م</span>
                    </p>
                    <p class="text-[11px] text-stone-400 mt-1">الرسوم الثابتة المحصلة</p>
                </div>

                <div
                    class="p-5 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs"
                >
                    <div class="flex items-center justify-between text-xs text-stone-400 font-bold mb-2">
                        <span>إيرادات العمولات</span>
                        <TrendingUp class="w-4 h-4 text-blue-500" />
                    </div>
                    <p class="text-2xl font-black text-stone-900 dark:text-white">
                        {{ fmt(platform_profit?.commission_revenue) }}
                        <span class="text-xs font-normal text-stone-400">ج.م</span>
                    </p>
                    <p class="text-[11px] text-stone-400 mt-1">نسبتنا من طلبات الموقع</p>
                </div>

                <div
                    class="p-5 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs"
                >
                    <div class="flex items-center justify-between text-xs text-stone-400 font-bold mb-2">
                        <span>المصروفات التشغيلية</span>
                        <TrendingDown class="w-4 h-4 text-red-500" />
                    </div>
                    <p class="text-2xl font-black text-stone-900 dark:text-white">
                        {{ fmt(platform_profit?.total_expenses) }}
                        <span class="text-xs font-normal text-stone-400">ج.م</span>
                    </p>
                    <p class="text-[11px] text-stone-400 mt-1">تكاليف السيرفرات والتسويق</p>
                </div>

                <div
                    class="p-5 rounded-3xl bg-gradient-to-br from-emerald-500 to-teal-700 text-white shadow-lg shadow-emerald-500/20"
                >
                    <div class="flex items-center justify-between text-xs opacity-90 font-bold mb-2">
                        <span>صافي أرباح المنصة</span>
                        <DollarSign class="w-4 h-4" />
                    </div>
                    <p class="text-2xl font-black">
                        {{ fmt(platform_profit?.net_profit) }}
                        <span class="text-xs font-normal opacity-80">ج.م</span>
                    </p>
                    <p class="text-[11px] opacity-80 mt-1">الإيراد الكلي بعد خصم المصروفات</p>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-3 rounded-2xl bg-orange-600 text-white shadow-md shadow-orange-600/20">
                        <FileSpreadsheet class="w-6 h-6" />
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-stone-900 dark:text-white flex items-center gap-2">
                            كشف حساب المطاعم والعمولات المستحقة
                            <span
                                class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-orange-100 dark:bg-orange-950/50 text-orange-600 border border-orange-200 dark:border-orange-800"
                            >
                                {{ filteredRestaurants.length }} مطعم
                            </span>
                        </h2>
                        <p class="text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                            سجل مالي مفصل لمبيعات كل مطعم، العمولات المقتطعة، المبالغ المحصلة، والمستحقات المعلقة ذمتهم
                        </p>
                    </div>
                </div>

                <Link
                    href="/admin/billing"
                    class="self-start md:self-auto inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-stone-900 hover:bg-stone-800 text-white dark:bg-white dark:text-white dark:hover:bg-stone-100 font-bold text-xs shadow-md transition"
                >
                    <Receipt class="w-4 h-4 text-orange-500" />
                    <span>فتح مركز التحصيل وإصدار الفواتير</span>
                    <ArrowUpRight class="w-4 h-4" />
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div
                    class="p-4 rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs"
                >
                    <p class="text-[11px] text-stone-400 font-bold">إجمالي مبيعات المطاعم</p>
                    <p class="text-lg font-black text-stone-900 dark:text-white mt-1">
                        {{ fmt(totalRestaurantGross) }}
                        <span class="text-xs font-normal text-stone-400">ج.م</span>
                    </p>
                </div>

                <div
                    class="p-4 rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs"
                >
                    <p class="text-[11px] text-stone-400 font-bold">إجمالي عمولات المنصة</p>
                    <p class="text-lg font-black text-orange-600 dark:text-orange-400 mt-1">
                        {{ fmt(totalPlatformCut) }}
                        <span class="text-xs font-normal text-stone-400">ج.م</span>
                    </p>
                </div>

                <div class="p-4 rounded-2xl bg-red-50/50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/40">
                    <p class="text-[11px] text-red-500 dark:text-red-400 font-bold flex items-center justify-between">
                        <span>العمولات المستحقة (غير محصلة)</span>
                        <AlertTriangle class="w-3.5 h-3.5" />
                    </p>
                    <p class="text-lg font-black text-red-600 dark:text-red-400 mt-1">
                        {{ fmt(totalUnpaidDue) }}
                        <span class="text-xs font-normal text-red-400">ج.م</span>
                    </p>
                </div>

                <div
                    class="p-4 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/40"
                >
                    <p
                        class="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold flex items-center justify-between"
                    >
                        <span>صافي أرباح المطاعم الكلية</span>
                        <CheckCircle2 class="w-3.5 h-3.5" />
                    </p>
                    <p class="text-lg font-black text-emerald-600 dark:text-emerald-400 mt-1">
                        {{ fmt(totalRestaurantNet) }}
                        <span class="text-xs font-normal text-emerald-500">ج.م</span>
                    </p>
                </div>
            </div>

            <div
                class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 p-3 bg-white dark:bg-stone-900 rounded-2xl border border-stone-200 dark:border-stone-800 shadow-xs"
            >
                <div class="relative flex-1">
                    <Search class="w-4 h-4 text-stone-400 absolute right-3.5 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="ابحث باسم المطعم..."
                        class="w-full pr-10 pl-4 py-2 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white placeholder-stone-400 focus:outline-none focus:border-orange-500 transition"
                    />
                </div>

                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 text-xs font-bold">
                    <button
                        type="button"
                        :class="[
                            'px-3 py-1.5 rounded-xl transition whitespace-nowrap',
                            statusFilter === 'ALL'
                                ? 'bg-stone-900 text-white dark:bg-white dark:text-white'
                                : 'bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-400 hover:text-stone-900',
                        ]"
                        @click="statusFilter = 'ALL'"
                    >
                        الكل ({{ restaurant_profits.length }})
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-3 py-1.5 rounded-xl transition whitespace-nowrap flex items-center gap-1',
                            statusFilter === 'DUE'
                                ? 'bg-red-600 text-white'
                                : 'bg-red-50 text-red-600 dark:bg-red-950/40 dark:text-red-400 hover:bg-red-100',
                        ]"
                        @click="statusFilter = 'DUE'"
                    >
                        <AlertTriangle class="w-3.5 h-3.5" />
                        عليه مستحقات ({{ dueCount }})
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-3 py-1.5 rounded-xl transition whitespace-nowrap flex items-center gap-1',
                            statusFilter === 'PAID'
                                ? 'bg-emerald-600 text-white'
                                : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400 hover:bg-emerald-100',
                        ]"
                        @click="statusFilter = 'PAID'"
                    >
                        <Check class="w-3.5 h-3.5" />
                        مسدد ({{ paidCount }})
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-3 py-1.5 rounded-xl transition whitespace-nowrap',
                            statusFilter === 'ACTIVE'
                                ? 'bg-stone-900 text-white dark:bg-white dark:text-white'
                                : 'bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-400',
                        ]"
                        @click="statusFilter = 'ACTIVE'"
                    >
                        نشط
                    </button>
                    <button
                        type="button"
                        :class="[
                            'px-3 py-1.5 rounded-xl transition whitespace-nowrap',
                            statusFilter === 'SUSPENDED'
                                ? 'bg-stone-900 text-white dark:bg-white dark:text-white'
                                : 'bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-400',
                        ]"
                        @click="statusFilter = 'SUSPENDED'"
                    >
                        موقوف
                    </button>
                </div>
            </div>

            <div
                class="rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-sm overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="record-cards w-full text-right text-xs">
                        <thead>
                            <tr
                                class="border-b border-stone-200 dark:border-stone-800 bg-stone-50/80 dark:bg-stone-800/60 text-stone-500 dark:text-stone-400 font-black text-[11px]"
                            >
                                <th class="py-4 px-4">المطعم ونظام العقد</th>
                                <th class="py-4 px-4 text-center">حركة الطلبات</th>
                                <th class="py-4 px-4">إجمالي المبيعات</th>
                                <th class="py-4 px-4">عمولة المنصة</th>
                                <th class="py-4 px-4">المبالغ المحصلة</th>
                                <th class="py-4 px-4 text-center">العمولات المستحقة</th>
                                <th class="py-4 px-4">صافي أرباح المطعم</th>
                                <th class="py-4 px-4 text-center">كشف الحساب</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-800/80">
                            <tr v-if="filteredRestaurants.length === 0">
                                <td colspan="8" class="py-16 text-center text-stone-400">
                                    <Store class="w-10 h-10 mx-auto mb-2 opacity-30" />
                                    <p class="font-bold text-sm">لا توجد مطاعم مطابقة للبحث أو التصفية الحالية</p>
                                </td>
                            </tr>
                            <template v-for="r in filteredRestaurants" :key="r.id">
                                <tr
                                    :class="[
                                        'transition-colors hover:bg-stone-50/70 dark:hover:bg-stone-800/50',
                                        (r.unpaid_due || 0) > 0 ? 'bg-red-500/[0.02]' : '',
                                        expandedRow === r.id ? 'bg-orange-50/40 dark:bg-orange-950/10' : '',
                                    ]"
                                >
                                    <td data-label="المطعم ونظام العقد" class="is-title py-4 px-4">
                                        <div class="flex items-center gap-2.5">
                                            <div
                                                :class="[
                                                    'w-2.5 h-2.5 rounded-full shrink-0',
                                                    r.status === 'ACTIVE'
                                                        ? 'bg-emerald-500 shadow-xs shadow-emerald-500'
                                                        : 'bg-red-500 shadow-xs shadow-red-500',
                                                ]"
                                            />
                                            <div>
                                                <Link
                                                    :href="`/admin/finance/restaurants/${r.id}`"
                                                    class="font-black text-stone-900 dark:text-white text-sm block hover:text-orange-600"
                                                >
                                                    {{ r.name }}
                                                </Link>
                                                <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                                    <span
                                                        v-if="Number(r.subscription_fee) > 0"
                                                        class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-200"
                                                    >
                                                        اشتراك {{ subscriptionPlanLabel(r.billing_cycle) }}:
                                                        {{ fmt(r.subscription_fee) }} ج
                                                    </span>
                                                    <span
                                                        v-if="r.commission_type === 'PERCENTAGE' || r.commission_type === 'HYBRID'"
                                                        class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300"
                                                    >
                                                        عمولة {{ r.commission_rate }}%
                                                    </span>
                                                    <span
                                                        v-else-if="Number(r.subscription_fee) <= 0"
                                                        class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300"
                                                    >
                                                        {{
                                                            r.commission_type === 'SUBSCRIPTION'
                                                                ? 'اشتراك'
                                                                : `عمولة ${r.commission_rate}%`
                                                        }}
                                                    </span>
                                                    <span
                                                        v-if="r.status === 'SUSPENDED'"
                                                        class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300"
                                                    >
                                                        موقوف
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td data-label="حركة الطلبات" class="py-4 px-4 text-center">
                                        <div
                                            class="inline-flex items-center gap-1.5 p-1 rounded-xl bg-stone-100 dark:bg-stone-800/80 text-[11px] font-bold"
                                        >
                                            <span
                                                class="px-2 py-0.5 rounded-lg bg-emerald-500/15 text-emerald-600 dark:text-emerald-400"
                                                title="طلبات مكتملة"
                                            >
                                                {{ r.delivered_orders }} تم
                                            </span>
                                            <span
                                                class="px-1.5 py-0.5 rounded-lg bg-red-500/15 text-red-600 dark:text-red-400"
                                                title="طلبات ملغية"
                                            >
                                                {{ r.cancelled_orders }} ملغي
                                            </span>
                                            <span
                                                v-if="r.pending_orders > 0"
                                                class="px-1.5 py-0.5 rounded-lg bg-amber-500/15 text-amber-600 dark:text-amber-400"
                                                title="طلبات معلقة"
                                            >
                                                {{ r.pending_orders }} معلق
                                            </span>
                                        </div>
                                        <span class="block text-[10px] text-stone-400 mt-1">
                                            الإجمالي: {{ r.total_orders }} طلب ({{ completionRate(r) }}%)
                                        </span>
                                    </td>

                                    <td data-label="إجمالي المبيعات" class="py-4 px-4 font-black text-stone-900 dark:text-white">
                                        {{ fmt(r.gross_revenue) }}
                                        <span class="text-[10px] font-normal text-stone-400">ج.م</span>
                                    </td>

                                    <td data-label="عمولة المنصة" class="py-4 px-4 font-black text-orange-600 dark:text-orange-400">
                                        {{ fmt(r.platform_cut) }}
                                        <span class="text-[10px] font-normal text-stone-400">ج.م</span>
                                    </td>

                                    <td data-label="المبالغ المحصلة" class="py-4 px-4 font-bold text-stone-700 dark:text-stone-300">
                                        {{ fmt(r.paid_amount || 0) }}
                                        <span class="text-[10px] font-normal text-stone-400">ج.م</span>
                                    </td>

                                    <td data-label="العمولات المستحقة" class="py-4 px-4 text-center">
                                        <div v-if="(r.unpaid_due || 0) > 0" class="inline-flex flex-col items-center">
                                            <span
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-red-100 dark:bg-red-950/60 text-red-700 dark:text-red-400 font-black text-xs border border-red-200 dark:border-red-900/50 shadow-xs"
                                            >
                                                <AlertTriangle class="w-3.5 h-3.5" />
                                                {{ fmt(r.unpaid_due) }} ج.م مستحق
                                            </span>
                                            <span
                                                v-if="r.overdue_count && r.overdue_count > 0"
                                                class="text-[9px] text-red-500 mt-0.5 font-bold"
                                            >
                                                {{ r.overdue_count }} فاتورة غير مسددة
                                            </span>
                                        </div>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 font-bold text-xs border border-emerald-200 dark:border-emerald-800"
                                        >
                                            <Check class="w-3.5 h-3.5" />
                                            مسدد بالكامل
                                        </span>
                                    </td>

                                    <td data-label="صافي أرباح المطعم" class="py-4 px-4">
                                        <span class="font-black text-emerald-600 dark:text-emerald-400 text-sm">
                                            {{ fmt(r.net_restaurant_earn) }}
                                            <span class="text-[10px] font-normal text-stone-400">ج.م</span>
                                        </span>
                                        <span class="block text-[10px] text-stone-400 mt-0.5">دخل المطعم الصافي</span>
                                    </td>

                                    <td data-label="كشف الحساب" class="is-actions py-4 px-4 text-center">
                                        <div class="inline-flex flex-col items-center gap-1.5">
                                        <Link
                                            :href="`/admin/finance/restaurants/${r.id}`"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-orange-600 text-white font-bold text-xs hover:bg-orange-500"
                                        >
                                            دخول الكشف
                                        </Link>
                                        <button
                                            type="button"
                                            :class="[
                                                'inline-flex items-center gap-1 px-3 py-1.5 rounded-xl font-bold text-xs transition',
                                                expandedRow === r.id
                                                    ? 'bg-orange-500 text-white shadow-xs'
                                                    : 'bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 hover:bg-stone-200 dark:hover:bg-stone-700',
                                            ]"
                                            @click="toggleExpand(r.id)"
                                        >
                                            <span>كشف تفصيلي</span>
                                            <ChevronUp v-if="expandedRow === r.id" class="w-3.5 h-3.5" />
                                            <ChevronDown v-else class="w-3.5 h-3.5" />
                                        </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr
                                    v-if="expandedRow === r.id"
                                    class="bg-gradient-to-b from-orange-50/50 to-white dark:from-stone-800/60 dark:to-stone-900"
                                >
                                    <td
                                        colspan="8"
                                        class="px-6 py-6 border-b border-orange-200/50 dark:border-stone-700"
                                    >
                                        <div class="space-y-4">
                                            <div
                                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-stone-200/70 dark:border-stone-700/60"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <Receipt class="w-4 h-4 text-orange-500" />
                                                    <h3 class="font-black text-stone-900 dark:text-white text-sm">
                                                        كشف الحساب التفصيلي لمطعم: {{ r.name }}
                                                    </h3>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <Link
                                                        :href="`/admin/finance/restaurants/${r.id}`"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-orange-600 hover:bg-orange-500 text-white text-xs font-bold transition shadow-xs"
                                                    >
                                                        <span>تجديد الاشتراك</span>
                                                        <ExternalLink class="w-3.5 h-3.5" />
                                                    </Link>
                                                    <Link
                                                        :href="`/admin/billing?restaurant_id=${r.id}`"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-stone-200 dark:border-stone-700 text-stone-700 dark:text-stone-200 text-xs font-bold transition"
                                                    >
                                                        <span>عرض الفواتير والتحصيل</span>
                                                    </Link>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs">
                                                <div
                                                    class="p-3.5 rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs"
                                                >
                                                    <p class="text-stone-400 mb-1 font-medium">إجمالي المبيعات (GMV)</p>
                                                    <p class="font-black text-stone-900 dark:text-white text-base">
                                                        {{ fmt(r.gross_revenue) }} ج.م
                                                    </p>
                                                    <p class="text-[10px] text-stone-400 mt-1">الطلبات المسلمة بنجاح</p>
                                                </div>

                                                <div
                                                    class="p-3.5 rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs"
                                                >
                                                    <p class="text-stone-400 mb-1 font-medium">رسوم التوصيل المقتطعة</p>
                                                    <p class="font-black text-blue-600 dark:text-blue-400 text-base">
                                                        {{ fmt(r.delivery_fees) }} ج.م
                                                    </p>
                                                    <p class="text-[10px] text-stone-400 mt-1">مستحقات كباتن التوصيل</p>
                                                </div>

                                                <div
                                                    class="p-3.5 rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs"
                                                >
                                                    <p class="text-stone-400 mb-1 font-medium">عمولة المنصة المقتطعة</p>
                                                    <p class="font-black text-orange-600 dark:text-orange-400 text-base">
                                                        {{ fmt(r.platform_cut) }} ج.م
                                                    </p>
                                                    <p class="text-[10px] text-stone-400 mt-1">
                                                        {{
                                                            r.commission_type === 'SUBSCRIPTION'
                                                                ? 'اشتراك شهري'
                                                                : `${r.commission_rate}% عمولة`
                                                        }}
                                                    </p>
                                                </div>

                                                <div
                                                    class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800"
                                                >
                                                    <p class="text-emerald-700 dark:text-emerald-400 mb-1 font-bold">
                                                        صافي أرباح المطعم
                                                    </p>
                                                    <p class="font-black text-emerald-600 dark:text-emerald-400 text-base">
                                                        {{ fmt(r.net_restaurant_earn) }} ج.م
                                                    </p>
                                                    <p class="text-[10px] text-emerald-600/70 mt-1">
                                                        المبلغ المتبقي للمطعم
                                                    </p>
                                                </div>

                                                <div
                                                    :class="[
                                                        'p-3.5 rounded-2xl border',
                                                        (r.unpaid_due || 0) > 0
                                                            ? 'bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-800 text-red-700 dark:text-red-300'
                                                            : 'bg-stone-50 dark:bg-stone-800/40 border-stone-200 dark:border-stone-800 text-stone-700 dark:text-stone-300',
                                                    ]"
                                                >
                                                    <p class="mb-1 font-bold">المستحقات المعلقة ذمته</p>
                                                    <p
                                                        :class="[
                                                            'text-base font-black',
                                                            (r.unpaid_due || 0) > 0
                                                                ? 'text-red-600 dark:text-red-400'
                                                                : 'text-emerald-600',
                                                        ]"
                                                    >
                                                        {{ fmt(r.unpaid_due) }} ج.م
                                                    </p>
                                                    <p class="text-[10px] opacity-80 mt-1">
                                                        {{
                                                            (r.unpaid_due || 0) > 0
                                                                ? 'واجبة السداد فوراً'
                                                                : 'لا توجد متأخرات'
                                                        }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div
                                                class="flex flex-wrap items-center gap-4 text-[11px] text-stone-500 dark:text-stone-400 pt-1 font-medium"
                                            >
                                                <span>
                                                    إجمالي الطلبات المستلمة:
                                                    <strong class="text-stone-900 dark:text-white">{{
                                                        r.total_orders
                                                    }}</strong>
                                                </span>
                                                <span>•</span>
                                                <span>
                                                    نسبة نجاح التسليم:
                                                    <strong class="text-emerald-600">{{ completionRate(r) }}%</strong>
                                                </span>
                                                <span>•</span>
                                                <span>
                                                    معدل الإلغاء:
                                                    <strong class="text-red-500">{{ cancelRate(r) }}%</strong>
                                                </span>
                                                <span>•</span>
                                                <span>
                                                    متوسط قيمة الطلب (AOV):
                                                    <strong class="text-stone-900 dark:text-white"
                                                        >{{ aov(r) }} ج.م</strong
                                                    >
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>

                        <tfoot
                            v-if="filteredRestaurants.length > 0"
                            class="border-t-2 border-stone-200 dark:border-stone-700 bg-stone-100/70 dark:bg-stone-800/70 text-xs font-black"
                        >
                            <tr>
                                <td data-label="المطعم ونظام العقد" class="is-title py-4 px-4 text-stone-900 dark:text-white">
                                    الإجمالي العام ({{ filteredRestaurants.length }} مطعم)
                                </td>
                                <td data-label="حركة الطلبات" class="py-4 px-4 text-center text-stone-700 dark:text-stone-300">
                                    <span class="text-emerald-600">{{ footerDelivered }} تم</span>
                                    /
                                    <span class="text-red-500">{{ footerCancelled }} ملغي</span>
                                </td>
                                <td data-label="إجمالي المبيعات" class="py-4 px-4 text-stone-900 dark:text-white">
                                    {{ fmt(footerGross) }} ج.م
                                </td>
                                <td data-label="عمولة المنصة" class="py-4 px-4 text-orange-600">{{ fmt(footerPlatform) }} ج.م</td>
                                <td data-label="المبالغ المحصلة" class="py-4 px-4 text-stone-800 dark:text-stone-200">
                                    {{ fmt(footerPaid) }} ج.م
                                </td>
                                <td data-label="العمولات المستحقة" class="py-4 px-4 text-center text-red-600">{{ fmt(footerUnpaid) }} ج.م</td>
                                <td data-label="صافي أرباح المطعم" class="py-4 px-4 text-emerald-600 text-sm">{{ fmt(footerNet) }} ج.م</td>
                                <td data-label="كشف الحساب" class="is-actions py-4 px-4" />
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div
            v-if="monthly_pnl.length > 0"
            class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs"
        >
            <div class="flex items-center gap-3 mb-5 pb-4 border-b border-stone-100 dark:border-stone-800">
                <BarChart3 class="w-5 h-5 text-orange-500" />
                <div>
                    <h2 class="text-base font-black text-stone-900 dark:text-white">
                        الأداء المالي الشهري للمنصة (آخر 12 شهراً)
                    </h2>
                    <p class="text-xs text-stone-400">مقارنة الإيرادات بالمصروفات وصافي الأرباح شهرياً</p>
                </div>
            </div>
            <div class="space-y-2 max-h-80 overflow-y-auto custom-scrollbar">
                <div
                    v-for="m in reversedMonthly"
                    :key="m.month"
                    class="flex items-center gap-3 text-xs group py-1"
                >
                    <span class="w-24 text-[11px] text-stone-400 font-bold shrink-0 text-left">{{ m.month }}</span>
                    <div class="flex-1 space-y-1">
                        <div
                            class="h-3 rounded-full bg-stone-100 dark:bg-stone-800 overflow-hidden"
                            :title="`الإيراد: ${fmt(m.revenue)} ج.م`"
                        >
                            <div
                                class="h-full rounded-full bg-emerald-500 transition-all"
                                :style="{ width: `${Math.round((m.revenue / maxMonthlyRevenue) * 100)}%` }"
                            />
                        </div>
                        <div
                            class="h-3 rounded-full bg-stone-100 dark:bg-stone-800 overflow-hidden"
                            :title="`المصروفات: ${fmt(m.expenses)} ج.م`"
                        >
                            <div
                                class="h-full rounded-full bg-red-400 transition-all"
                                :style="{ width: `${Math.round((m.expenses / maxMonthlyRevenue) * 100)}%` }"
                            />
                        </div>
                    </div>
                    <div class="text-right w-28 shrink-0">
                        <span
                            :class="[
                                'font-black text-sm block',
                                m.profit >= 0 ? 'text-emerald-600' : 'text-red-500',
                            ]"
                        >
                            {{ m.profit >= 0 ? '+' : '' }}{{ fmt(m.profit) }} ج.م
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
