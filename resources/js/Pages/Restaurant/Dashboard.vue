<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import type { Restaurant, Order } from '../../Types';
import {
    ShoppingBag,
    Clock,
    DollarSign,
    Bike,
    AlertCircle,
    ArrowRight,
    Eye,
    CreditCard,
    Calendar,
} from '@lucide/vue';
import { availabilityMeta, resolveAvailability } from '../../lib/restaurantAvailability';
import { subscriptionPlanLabel } from '../../lib/subscriptionPlans';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        restaurant: Restaurant;
        stats: {
            orders_today: number;
            pending_orders: number;
            preparing_orders: number;
            ready_orders: number;
            delivered_orders: number;
            cancelled_orders: number;
            revenue_today: number;
            revenue_week: number;
            revenue_month: number;
            active_drivers: number;
        };
        top_items?: { name: string; total_qty: number; total_revenue: number }[];
        recent_orders?: Order[];
        billing_info?: {
            payment_due_date?: string;
            days_remaining?: number | null;
            is_overdue: boolean;
            monthly_subscription_fee: number;
            billing_cycle?: string;
            subscription_ends_at?: string | null;
            grace_period_days?: number | null;
            has_subscription?: boolean;
            has_unpaid_invoice: boolean;
            unpaid_amount: number;
            invoice_number?: string;
        };
    }>(),
    {
        top_items: () => [],
        recent_orders: () => [],
    },
);

const availability = computed(() => resolveAvailability(props.restaurant));
const meta = computed(() => availabilityMeta(availability.value));
const billingDays = computed(() =>
    typeof props.billing_info?.days_remaining === 'number' ? props.billing_info.days_remaining : null,
);

const handleAdvanceStatus = (orderId: number, status: string): void => {
    router.patch(`/restaurant/orders/${orderId}/status`, { status });
};

const formatOrderTime = (createdAt: string): string =>
    new Date(createdAt).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
</script>

<template>
    <Head :title="`${restaurant.name} — لوحة التحكم`" />

    <div class="space-y-6">
        <section class="relative overflow-hidden rounded-3xl bg-gradient-to-l from-orange-500 to-amber-500 px-5 py-6 text-white shadow-lg shadow-orange-500/20 sm:px-7 sm:py-7">
            <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="mb-2 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold backdrop-blur">
                        <span
                            :class="[
                                'h-2 w-2 rounded-full',
                                availability === 'OPEN' ? 'bg-emerald-300' : availability === 'BUSY' ? 'bg-amber-300' : 'bg-stone-300',
                            ]"
                        />
                        {{ meta.label }}
                        <template v-if="availability === 'CLOSED'"> — غيّر الحالة من أعلى الصفحة</template>
                    </div>
                    <h1 class="text-2xl font-black sm:text-3xl">{{ restaurant.name }}</h1>
                    <p class="mt-1.5 max-w-lg text-sm text-orange-50/90">
                        تابع الطلبات الجديدة وحرّكها من التأكيد للتجهيز حتى الاستلام.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        href="/restaurant/menu"
                        prefetch
                        class="rounded-xl border border-white/30 bg-white/10 px-4 py-2.5 text-xs font-bold transition hover:bg-white/20"
                    >
                        المنيو
                    </Link>
                    <Link
                        href="/restaurant/orders?status=PENDING"
                        prefetch
                        class="rounded-xl bg-white px-4 py-2.5 text-xs font-black text-orange-700 transition hover:bg-orange-50"
                    >
                        طلبات جديدة ({{ stats.pending_orders }})
                    </Link>
                </div>
            </div>

            <div class="relative mt-5 grid grid-cols-3 gap-2 sm:max-w-lg sm:gap-3">
                <div class="rounded-2xl bg-white/15 px-3 py-3 text-center backdrop-blur">
                    <p class="text-xl font-black text-white">{{ stats.pending_orders }}</p>
                    <p class="mt-0.5 text-[10px] font-bold text-orange-100">بانتظارك</p>
                </div>
                <div class="rounded-2xl bg-white/15 px-3 py-3 text-center backdrop-blur">
                    <p class="text-xl font-black text-white">{{ stats.preparing_orders }}</p>
                    <p class="mt-0.5 text-[10px] font-bold text-orange-100">قيد التجهيز</p>
                </div>
                <div class="rounded-2xl bg-white/15 px-3 py-3 text-center backdrop-blur">
                    <p class="text-xl font-black text-white">{{ stats.ready_orders }}</p>
                    <p class="mt-0.5 text-[10px] font-bold text-orange-100">جاهز</p>
                </div>
            </div>
        </section>

        <div
            v-if="stats.pending_orders > 0"
            class="flex flex-col gap-4 rounded-3xl border border-amber-200 bg-amber-50 p-4 text-amber-900 sm:flex-row sm:items-center sm:justify-between sm:p-5"
        >
            <div class="flex items-center gap-3">
                <AlertCircle class="h-7 w-7 shrink-0 text-amber-600" />
                <div>
                    <h2 class="text-sm font-black">عندك {{ stats.pending_orders }} طلب جديد</h2>
                    <p class="text-xs text-amber-700">أكدها بسرعة عشان التجهيز يبدأ.</p>
                </div>
            </div>
            <Link
                href="/restaurant/orders?status=PENDING"
                class="rounded-xl bg-amber-600 px-5 py-2.5 text-center text-xs font-bold text-white transition hover:bg-amber-700"
            >
                عرض الطلبات
            </Link>
        </div>

        <div
            v-if="billing_info"
            :class="[
                'flex flex-col gap-4 rounded-3xl border p-5 md:flex-row md:items-center md:justify-between',
                billing_info.is_overdue
                    ? 'border-red-200 bg-red-50'
                    : billingDays !== null && billingDays <= 3
                      ? 'border-amber-200 bg-amber-50'
                      : 'border-stone-200 bg-white',
            ]"
        >
            <div class="flex items-start gap-3 sm:items-center">
                <div
                    :class="[
                        'rounded-2xl p-3',
                        billing_info.is_overdue ? 'bg-red-100 text-red-600' : 'bg-orange-100 text-orange-600',
                    ]"
                >
                    <CreditCard class="h-5 w-5" />
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-sm font-bold text-stone-900">
                            <template v-if="billing_info.has_subscription">
                                اشتراك {{ subscriptionPlanLabel(billing_info.billing_cycle) }}:
                            </template>
                            <template v-else>الاشتراك الشهري:</template>
                            {{ billing_info.monthly_subscription_fee }} ج.م
                        </h3>
                        <span
                            v-if="billing_info.is_overdue"
                            class="rounded-full bg-red-100 px-2.5 py-0.5 text-[11px] font-bold text-red-700"
                        >
                            متأخر الدفع
                        </span>
                        <span v-else class="rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700">
                            الحساب نشط
                        </span>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-stone-500">
                        <span v-if="billing_info.payment_due_date" class="inline-flex items-center gap-1">
                            <Calendar class="h-3.5 w-3.5" />
                            موعد السداد: <strong class="text-stone-800">{{ billing_info.payment_due_date }}</strong>
                        </span>
                        <span v-if="billing_info.subscription_ends_at">
                            ينتهي {{ billing_info.subscription_ends_at }}
                            <template v-if="billing_info.grace_period_days">
                                ثم {{ billing_info.grace_period_days }} يوم سماح
                            </template>
                        </span>
                        <span
                            v-if="billingDays !== null"
                            :class="[
                                'font-bold',
                                billingDays < 0 ? 'text-red-600' : billingDays <= 3 ? 'text-amber-600' : 'text-emerald-600',
                            ]"
                        >
                            <template v-if="billingDays < 0">متأخر {{ Math.abs(billingDays) }} يوم</template>
                            <template v-else-if="billingDays === 0">مستحق اليوم</template>
                            <template v-else>متبقي {{ billingDays }} يوم</template>
                        </span>
                        <span v-if="billing_info.has_unpaid_invoice" class="font-semibold text-orange-600">
                            فاتورة معلقة: {{ billing_info.unpaid_amount }} ج.م
                        </span>
                    </div>
                </div>
            </div>

            <Link
                href="/restaurant/billing"
                class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-orange-500 px-5 py-2.5 text-xs font-bold text-white transition hover:bg-orange-600"
            >
                الفواتير والدفع
                <ArrowRight class="h-3.5 w-3.5 rotate-180" />
            </Link>
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
            <div class="rounded-3xl border border-stone-200 bg-white p-5 shadow-xs">
                <div class="mb-2 flex items-center justify-between text-xs font-bold text-stone-400">
                    <span>مبيعات اليوم</span>
                    <DollarSign class="h-4 w-4 text-emerald-500" />
                </div>
                <p class="text-2xl font-black text-stone-900">{{ stats.revenue_today.toFixed(0) }} ج.م</p>
                <p class="mt-1 text-[11px] text-stone-500">هذا الأسبوع: {{ stats.revenue_week.toFixed(0) }} ج.م</p>
            </div>

            <div class="rounded-3xl border border-stone-200 bg-white p-5 shadow-xs">
                <div class="mb-2 flex items-center justify-between text-xs font-bold text-stone-400">
                    <span>طلبات اليوم</span>
                    <ShoppingBag class="h-4 w-4 text-orange-500" />
                </div>
                <p class="text-2xl font-black text-stone-900">{{ stats.orders_today }}</p>
                <p class="mt-1 text-[11px] font-bold text-emerald-600">{{ stats.delivered_orders }} تم تسليمها</p>
            </div>

            <div class="rounded-3xl border border-stone-200 bg-white p-5 shadow-xs">
                <div class="mb-2 flex items-center justify-between text-xs font-bold text-stone-400">
                    <span>في المطبخ</span>
                    <Clock class="h-4 w-4 text-amber-500" />
                </div>
                <p class="text-2xl font-black text-stone-900">{{ stats.preparing_orders }}</p>
                <p class="mt-1 text-[11px] font-bold text-purple-600">{{ stats.ready_orders }} جاهزة للطيار</p>
            </div>

            <div class="rounded-3xl border border-stone-200 bg-white p-5 shadow-xs">
                <div class="mb-2 flex items-center justify-between text-xs font-bold text-stone-400">
                    <span>كباتن متاحين</span>
                    <Bike class="h-4 w-4 text-teal-500" />
                </div>
                <p class="text-2xl font-black text-stone-900">{{ stats.active_drivers }}</p>
                <p class="mt-1 text-[11px] text-stone-500">جاهزون للاستلام</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="rounded-3xl border border-stone-200 bg-white p-5 shadow-xs lg:col-span-2 sm:p-6">
                <div class="mb-5 flex items-center justify-between border-b border-stone-100 pb-3">
                    <div>
                        <h2 class="text-base font-black text-stone-900">آخر الطلبات</h2>
                        <p class="text-xs text-stone-400">أحدث الطلبات الواردة لفرعك</p>
                    </div>
                    <Link href="/restaurant/orders" class="text-xs font-bold text-orange-600 hover:underline">
                        كل الطلبات
                    </Link>
                </div>

                <p v-if="recent_orders.length === 0" class="py-10 text-center text-xs text-stone-400">لسه مفيش طلبات.</p>
                <div v-else class="divide-y divide-stone-100 text-xs">
                    <div
                        v-for="order in recent_orders"
                        :key="order.id"
                        class="flex flex-col justify-between gap-3 py-3.5 sm:flex-row sm:items-center"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-bold text-orange-600">{{ order.order_number }}</span>
                                <span class="text-stone-300">•</span>
                                <span class="font-bold text-stone-800">{{ order.customer?.user?.name || 'عميل' }}</span>
                            </div>
                            <p class="mt-0.5 text-[11px] text-stone-400">
                                {{ formatOrderTime(order.created_at) }}
                                <template v-if="order.address"> • {{ order.address }}</template>
                            </p>
                        </div>

                        <div class="flex items-center justify-between gap-3 sm:justify-end">
                            <span class="text-sm font-black text-stone-900">{{ order.total_amount }} ج.م</span>

                            <button
                                v-if="order.status === 'PENDING'"
                                type="button"
                                class="rounded-lg bg-blue-600 px-3 py-1.5 font-bold text-white hover:bg-blue-700"
                                @click="handleAdvanceStatus(order.id, 'CONFIRMED')"
                            >
                                تأكيد
                            </button>
                            <button
                                v-if="order.status === 'CONFIRMED'"
                                type="button"
                                class="rounded-lg bg-orange-600 px-3 py-1.5 font-bold text-white hover:bg-orange-700"
                                @click="handleAdvanceStatus(order.id, 'PREPARING')"
                            >
                                تجهيز
                            </button>
                            <button
                                v-if="order.status === 'PREPARING'"
                                type="button"
                                class="rounded-lg bg-purple-600 px-3 py-1.5 font-bold text-white hover:bg-purple-700"
                                @click="handleAdvanceStatus(order.id, 'READY_FOR_PICKUP')"
                            >
                                جاهز
                            </button>

                            <Link
                                :href="`/restaurant/orders/${order.id}`"
                                class="rounded-lg bg-stone-100 p-1.5 text-stone-600 hover:text-orange-600"
                                title="التفاصيل"
                            >
                                <Eye class="h-4 w-4" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-stone-200 bg-white p-5 shadow-xs sm:p-6">
                <h2 class="mb-1 text-base font-black text-stone-900">الأكثر مبيعاً</h2>
                <p class="mb-5 text-xs text-stone-400">آخر 30 يوم</p>

                <p v-if="top_items.length === 0" class="py-6 text-center text-xs text-stone-400">مفيش بيانات كفاية لسه.</p>
                <div v-else class="space-y-4">
                    <div
                        v-for="(item, idx) in top_items"
                        :key="idx"
                        class="flex items-center justify-between text-xs"
                    >
                        <div class="flex items-center gap-2">
                            <span class="flex h-5 w-5 items-center justify-center rounded-md bg-stone-100 text-[10px] font-bold text-stone-600">
                                {{ idx + 1 }}
                            </span>
                            <span class="max-w-[140px] truncate font-bold text-stone-800">{{ item.name }}</span>
                        </div>
                        <div class="text-left">
                            <span class="font-bold text-stone-900">{{ item.total_qty }} طلب</span>
                            <span class="block text-[10px] text-stone-400">{{ item.total_revenue }} ج.م</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
