<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    Store,
    DollarSign,
    AlertCircle,
    TrendingUp,
    ArrowRight,
    GraduationCap,
    Plus,
} from '@lucide/vue';
import { Order, Restaurant } from '../../Types';

withDefaults(
    defineProps<{
        stats: {
            total_restaurants: number;
            active_restaurants: number;
            suspended_restaurants: number;
            total_customers: number;
            total_orders: number;
            orders_today: number;
            revenue_today: number;
            revenue_this_month: number;
            platform_profit: number;
            total_expenses: number;
            outstanding_collections: number;
            overdue_invoices: number;
        };
        revenue_chart: {
            date: string;
            revenue: number;
            orders: number;
        }[];
        profit_chart: unknown[];
        recent_orders: Order[];
        top_restaurants: (Restaurant & { orders_sum_total_amount?: number })[];
    }>(),
    {
        revenue_chart: () => [],
        profit_chart: () => [],
        recent_orders: () => [],
        top_restaurants: () => [],
    },
);
</script>

<template>
    <Head title="لوحة التحكم المركزية" />

    <div class="space-y-8">
        <section class="relative overflow-hidden rounded-[2rem] bg-gradient-to-r from-orange-600 via-amber-600 to-orange-500 px-6 py-7 text-white shadow-xl shadow-orange-500/20 sm:px-8 sm:py-8">
            <div class="absolute -left-16 -top-20 h-56 w-56 rounded-full bg-amber-300/30 blur-3xl" />
            <div class="absolute bottom-0 right-1/3 h-32 w-32 rounded-full bg-white/10 blur-2xl" />
            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="mb-2 text-[11px] font-black tracking-[0.22em] text-orange-100 uppercase">مركز قيادة المنصة</p>
                    <h1 class="text-2xl font-black sm:text-3xl">صباح الخير، كل عملياتك في مكان واحد</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-orange-50/90">
                        راقب الطلبات والتحصيل والشركاء بسرعة، ثم تحرك مباشرة إلى المهمة المهمة الآن.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:flex">
                    <div class="rounded-2xl border border-white/20 bg-white/15 px-4 py-3 backdrop-blur">
                        <p class="text-[10px] font-bold text-orange-100">طلبات اليوم</p>
                        <p class="mt-1 text-xl font-black">{{ stats.orders_today }}</p>
                    </div>
                    <Link
                        href="/admin/orders"
                        prefetch
                        class="inline-flex items-center justify-center rounded-2xl bg-white px-4 py-3 text-xs font-black text-orange-700 transition hover:bg-orange-50"
                    >
                        متابعة الطلبات
                        <ArrowRight class="mr-1 h-4 w-4" />
                    </Link>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="group relative overflow-hidden p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-500/10">
                <div class="flex items-center justify-between text-xs text-stone-400 font-bold mb-2">
                    <span>إجمالي المبيعات (GMV هذا الشهر)</span>
                    <DollarSign class="w-4 h-4 text-emerald-500" />
                </div>
                <p class="text-2xl sm:text-3xl font-black text-stone-900 dark:text-white">
                    {{ stats.revenue_this_month.toFixed(1) }} ج.م
                </p>
                <p class="text-[11px] text-stone-500 mt-1">
                    اليوم: {{ stats.revenue_today.toFixed(1) }} ج.م
                </p>
            </div>

            <div class="group relative overflow-hidden p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-orange-500/10">
                <div class="flex items-center justify-between text-xs text-stone-400 font-bold mb-2">
                    <span>صافي أرباح المنصة (العمولات)</span>
                    <TrendingUp class="w-4 h-4 text-orange-500" />
                </div>
                <p class="text-2xl sm:text-3xl font-black text-orange-600 dark:text-orange-400">
                    {{ stats.platform_profit.toFixed(1) }} ج.م
                </p>
                <p class="text-[11px] text-stone-500 mt-1">
                    بعد خصم المصروفات
                </p>
            </div>

            <div class="group relative overflow-hidden p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-violet-500/10">
                <div class="flex items-center justify-between text-xs text-stone-400 font-bold mb-2">
                    <span>المطاعم الشريكة النشطة</span>
                    <Store class="w-4 h-4 text-purple-500" />
                </div>
                <p class="text-2xl sm:text-3xl font-black text-stone-900 dark:text-white">
                    {{ stats.active_restaurants }} / {{ stats.total_restaurants }}
                </p>
                <p class="text-[11px] text-stone-500 mt-1">
                    معتمدة في مدينة برج العرب
                </p>
            </div>

            <div class="group relative overflow-hidden p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-500/10">
                <div class="flex items-center justify-between text-xs text-stone-400 font-bold mb-2">
                    <span>العملاء المسجلين</span>
                    <GraduationCap class="w-4 h-4 text-blue-500" />
                </div>
                <p class="text-2xl sm:text-3xl font-black text-stone-900 dark:text-white">
                    {{ stats.total_customers }}
                </p>
                <p class="text-[11px] text-stone-500 mt-1">
                    إجمالي الطلبات: {{ stats.total_orders }}
                </p>
            </div>
        </div>

        <div
            v-if="stats.overdue_invoices > 0 || stats.outstanding_collections > 0"
            class="p-5 rounded-3xl bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-900/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs"
        >
            <div class="flex items-center gap-3">
                <AlertCircle class="w-5 h-5 text-amber-600 shrink-0" />
                <div>
                    <span class="font-black text-amber-900 dark:text-amber-200 text-sm block">تنبيهات التحصيل المالي</span>
                    <span class="text-amber-700 dark:text-amber-300">
                        يوجد مستحقات متأخرة بقيمة {{ stats.outstanding_collections }} ج.م وعدد {{ stats.overdue_invoices }} فواتير اشتراك تحتاج متابعة.
                    </span>
                </div>
            </div>
            <div class="flex gap-2">
                <Link href="/admin/invoices" class="px-3.5 py-1.5 rounded-xl bg-amber-600 text-white font-bold hover:bg-amber-700 transition">
                    مراجعة الفواتير
                </Link>
                <Link href="/admin/collections" class="px-3.5 py-1.5 rounded-xl bg-white dark:bg-stone-800 border border-stone-300 dark:border-stone-700 text-stone-800 dark:text-stone-200 font-bold">
                    التحصيلات
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <Link
                href="/admin/restaurants"
                class="p-4 rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 hover:border-orange-500 flex items-center gap-3 transition group shadow-xs"
            >
                <div class="w-9 h-9 rounded-xl bg-orange-100 dark:bg-orange-950 text-orange-600 flex items-center justify-center">
                    <Plus class="w-4 h-4" />
                </div>
                <div>
                    <span class="text-xs font-bold text-stone-900 dark:text-white block group-hover:text-orange-600">إضافة مطعم</span>
                    <span class="text-[10px] text-stone-400">شريك جديد</span>
                </div>
            </Link>

            <Link
                href="/admin/customers"
                class="p-4 rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 hover:border-orange-500 flex items-center gap-3 transition group shadow-xs"
            >
                <div class="w-9 h-9 rounded-xl bg-blue-100 dark:bg-blue-950 text-blue-600 flex items-center justify-center">
                    <GraduationCap class="w-4 h-4" />
                </div>
                <div>
                    <span class="text-xs font-bold text-stone-900 dark:text-white block group-hover:text-orange-600">توثيق الكارنيهات</span>
                    <span class="text-[10px] text-stone-400">مراجعة بطاقات الطلاب</span>
                </div>
            </Link>

            <Link
                href="/admin/finance"
                class="p-4 rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 hover:border-orange-500 flex items-center gap-3 transition group shadow-xs"
            >
                <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-600 flex items-center justify-center">
                    <DollarSign class="w-4 h-4" />
                </div>
                <div>
                    <span class="text-xs font-bold text-stone-900 dark:text-white block group-hover:text-orange-600">الأرباح والمصروفات</span>
                    <span class="text-[10px] text-stone-400">التقرير المالي العام</span>
                </div>
            </Link>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-stone-100 dark:border-stone-800">
                    <div>
                        <h2 class="text-base font-black text-stone-900 dark:text-white">
                            أحدث طلبات المنصة الحية
                        </h2>
                        <p class="text-xs text-stone-400">متابعة العمليات بين المطاعم والعملاء والطيارين</p>
                    </div>
                    <Link href="/admin/orders" class="text-xs font-bold text-orange-600 hover:underline">
                        كل الطلبات
                    </Link>
                </div>

                <p v-if="recent_orders.length === 0" class="text-center py-8 text-xs text-stone-400">
                    لا توجد طلبات مسجلة بعد.
                </p>
                <div v-else class="divide-y divide-stone-100 dark:divide-stone-800 text-xs">
                    <div
                        v-for="order in recent_orders"
                        :key="order.id"
                        class="py-3.5 flex items-center justify-between"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-orange-600">{{ order.order_number }}</span>
                                <span class="text-stone-400">•</span>
                                <span class="font-bold text-stone-900 dark:text-white">{{ order.restaurant?.name }}</span>
                            </div>
                            <p class="text-[11px] text-stone-500 mt-0.5">
                                العميل: {{ order.customer?.user?.name || 'عميل' }} • {{ order.address }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="font-black text-stone-900 dark:text-white text-sm">
                                {{ order.total_amount }} ج.م
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300">
                                {{ order.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                <h2 class="text-base font-black text-stone-900 dark:text-white mb-1">
                    أعلى المطاعم مبيعاً
                </h2>
                <p class="text-xs text-stone-400 mb-4">هذا الشهر</p>

                <div class="space-y-3 text-xs">
                    <div
                        v-for="(r, idx) in top_restaurants"
                        :key="r.id"
                        class="p-3 rounded-2xl bg-stone-50 dark:bg-stone-800/50 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-2.5">
                            <span class="w-6 h-6 rounded-lg bg-orange-100 dark:bg-orange-950 text-orange-600 font-bold flex items-center justify-center text-[10px]">
                                {{ idx + 1 }}
                            </span>
                            <div>
                                <h3 class="font-bold text-stone-900 dark:text-white">{{ r.name }}</h3>
                                <span class="text-[10px] text-stone-400">{{ r.status }}</span>
                            </div>
                        </div>
                        <span class="font-black text-emerald-600 dark:text-emerald-400">
                            {{ r.orders_sum_total_amount || 0 }} ج.م
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
