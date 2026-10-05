<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    Banknote,
    Bike,
    CalendarRange,
    CircleDollarSign,
    ClipboardList,
    Receipt,
    Store,
    Users,
    UtensilsCrossed,
    Wallet,
} from '@lucide/vue';

interface Period {
    key: 'today' | 'week' | 'month' | 'all';
    label: string;
    from: string | null;
    to: string | null;
}

interface Bucket {
    key: string;
    label: string;
    hint: string;
    count: number;
    amount: number;
}

interface RestaurantRow {
    id: number;
    name: string;
    status: string;
    total_orders: number;
    delivered_orders: number;
    cancelled_orders: number;
    customer_paid: number;
    delivery_fees: number;
    platform_commission: number;
    restaurant_net: number;
    unpaid_due: number;
}

interface MoneyLine {
    label: string;
    hint: string;
    value: number;
    tone?: 'good' | 'bad' | 'muted';
}

const props = defineProps<{
    period: Period;
    platform: {
        subscription_collected: number;
        commission_collected: number;
        other_collected: number;
        collected_total: number;
        expenses: number;
        net_profit: number;
        outstanding: number;
    };
    orders: {
        total: number;
        buckets: Bucket[];
        completion_rate: number;
        cancellation_rate: number;
    };
    delivered_money: {
        orders: number;
        customer_paid: number;
        food_subtotal: number;
        discounts: number;
        student_discounts: number;
        delivery_fees: number;
        service_fees: number;
        platform_commission: number;
        restaurant_net: number;
        average_order: number;
    };
    payments: Array<{ method: string; label: string; count: number; amount: number }>;
    restaurants: RestaurantRow[];
    customers: { registered: number; ordered: number; new: number; returning: number };
    delivery: {
        active_drivers: number;
        available_drivers: number;
        fees: number;
        average_minutes: number;
        on_the_way: number;
    };
    expenses: Array<{ name: string; amount: number }>;
    daily: Array<{ date: string; label: string; revenue: number; delivered: number; cancelled: number }>;
    top_items: Array<{ name: string; restaurant: string; qty: number; revenue: number }>;
}>();

const periods: Array<{ key: Period['key']; label: string }> = [
    { key: 'today', label: 'اليوم' },
    { key: 'week', label: 'هذا الأسبوع' },
    { key: 'month', label: 'هذا الشهر' },
    { key: 'all', label: 'كل الوقت' },
];

const money = (value: number | string | null | undefined): string => {
    const amount = Number(value ?? 0);
    if (!Number.isFinite(amount)) {
        return '0';
    }

    return amount.toLocaleString('en', { maximumFractionDigits: 2 });
};

const platformLines = computed<MoneyLine[]>(() => [
    {
        label: 'اشتراكات اتحصّلت',
        hint: 'فواتير اشتراك مدفوعة في الفترة',
        value: props.platform.subscription_collected,
    },
    {
        label: 'عمولة اتحصّلت',
        hint: 'فواتير عمولة مدفوعة، مش العمولة المحسوبة على الطلب',
        value: props.platform.commission_collected,
    },
    {
        label: 'فواتير أخرى',
        hint: 'فواتير يدوية أو مجمّعة اتدفعت',
        value: props.platform.other_collected,
        tone: 'muted',
    },
    {
        label: 'المصروفات',
        hint: 'اللي اتصرف في نفس الفترة',
        value: props.platform.expenses,
        tone: 'bad',
    },
]);

const orderLines = computed<MoneyLine[]>(() => [
    {
        label: 'العميل دفع',
        hint: 'إجمالي الطلبات المسلّمة',
        value: props.delivered_money.customer_paid,
    },
    {
        label: 'قيمة الأكل',
        hint: 'قبل الخصم والتوصيل',
        value: props.delivered_money.food_subtotal,
        tone: 'muted',
    },
    {
        label: 'خصم الطلاب',
        hint: 'اتخصم من قيمة الأكل',
        value: props.delivered_money.student_discounts,
        tone: 'muted',
    },
    {
        label: 'خصومات أخرى',
        hint: 'أي خصم غير خصم الطالب',
        value: props.delivered_money.discounts,
        tone: 'muted',
    },
    {
        label: 'رسوم التوصيل',
        hint: 'داخلة في اللي دفعه العميل',
        value: props.delivered_money.delivery_fees,
    },
    {
        label: 'رسوم الخدمة',
        hint: 'لو موجودة على الطلب',
        value: props.delivered_money.service_fees,
        tone: 'muted',
    },
    {
        label: 'عمولة وصلة على الطلب',
        hint: 'محسوبة على الطلب، ولسه ممكن ما تتحصّلش في فاتورة',
        value: props.delivered_money.platform_commission,
        tone: 'bad',
    },
    {
        label: 'صافي المطاعم',
        hint: 'اللي دفعه العميل − التوصيل − عمولة وصلة',
        value: props.delivered_money.restaurant_net,
        tone: 'good',
    },
]);

const maxDailyRevenue = computed(() => Math.max(...props.daily.map((day) => day.revenue), 1));
const maxPayment = computed(() => Math.max(...props.payments.map((payment) => payment.amount), 1));

const bucketClass = (key: string): string => {
    if (key === 'delivered') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300';
    }
    if (key === 'stopped') {
        return 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300';
    }
    if (key === 'on_the_way') {
        return 'border-indigo-200 bg-indigo-50 text-indigo-800 dark:border-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-300';
    }
    if (key === 'kitchen') {
        return 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300';
    }

    return 'border-stone-200 bg-stone-50 text-stone-700 dark:border-stone-700 dark:bg-stone-800/60 dark:text-stone-200';
};

const restaurantStatus = (status: string): string => {
    if (status === 'ACTIVE') {
        return 'شغّال';
    }
    if (status === 'SUSPENDED') {
        return 'موقوف';
    }

    return status;
};
</script>

<template>
    <Head title="التقارير" />

    <div class="mx-auto max-w-7xl space-y-6 pb-12" dir="rtl">
        <section class="overflow-hidden rounded-[2rem] border border-orange-200 bg-gradient-to-l from-orange-500 to-amber-500 p-6 text-white shadow-lg shadow-orange-500/20 sm:p-8 dark:border-orange-900">
            <div>
                <p class="text-xs font-black tracking-[0.18em] text-orange-100">كل حاجة في مكان واحد</p>
                <h1 class="mt-2 text-3xl font-black">التقارير</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-orange-50">
                    الصفحة متقسمة عشان تفرّق بين فلوس وصلة اللي اتحصّلت، وفلوس الطلبات، وحالة كل طلب.
                    غيّر الفترة من هنا، والأرقام كلها تتغير مع بعض.
                </p>
            </div>
            <div class="mt-5 flex flex-wrap gap-2">
                <Link
                    v-for="item in periods"
                    :key="item.key"
                    :href="`/admin/reports?period=${item.key}`"
                    preserve-scroll
                    :class="[
                        'rounded-2xl px-4 py-2 text-sm font-black transition',
                        period.key === item.key
                            ? 'bg-white text-orange-700'
                            : 'bg-white/15 text-white hover:bg-white/25',
                    ]"
                >
                    {{ item.label }}
                </Link>
            </div>
            <p class="mt-4 text-xs font-bold text-orange-100">
                الفترة الحالية: {{ period.label }}
                <span v-if="period.from && period.to"> — من {{ period.from }} إلى {{ period.to }}</span>
            </p>
        </section>

        <section class="grid gap-3 md:grid-cols-3">
            <article class="rounded-3xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900">
                <p class="text-xs font-bold text-stone-400">دخل وصلة المحصّل</p>
                <p class="mt-2 text-3xl font-black text-stone-900 dark:text-white">{{ money(platform.collected_total) }} <span class="text-sm">ج.م</span></p>
                <p class="mt-2 text-xs leading-5 text-stone-500">اشتراكات + عمولات + فواتير أخرى اتدفعت في الفترة.</p>
            </article>
            <article class="rounded-3xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900">
                <p class="text-xs font-bold text-stone-400">صافي ربح وصلة</p>
                <p :class="['mt-2 text-3xl font-black', platform.net_profit >= 0 ? 'text-emerald-600' : 'text-red-600']">
                    {{ money(platform.net_profit) }} <span class="text-sm">ج.م</span>
                </p>
                <p class="mt-2 text-xs leading-5 text-stone-500">المحصّل ناقص المصروفات. مش شامل عمولة لسه ما اتحصّلتش.</p>
            </article>
            <article class="rounded-3xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900">
                <p class="text-xs font-bold text-stone-400">لسه مستحق على المطاعم</p>
                <p class="mt-2 text-3xl font-black text-amber-600">{{ money(platform.outstanding) }} <span class="text-sm">ج.م</span></p>
                <p class="mt-2 text-xs leading-5 text-stone-500">فواتير مش مدفوعة من كل الفترات، مش من الفترة المختارة بس.</p>
            </article>
        </section>

        <section class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
            <div class="mb-5 flex items-center gap-3">
                <span class="rounded-2xl bg-emerald-100 p-2 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                    <Wallet class="h-5 w-5" />
                </span>
                <div>
                    <h2 class="text-lg font-black">1. فلوس وصلة</h2>
                    <p class="text-xs text-stone-400">اللي دخل المنصة فعليًا، واللي اتصرف.</p>
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <article
                    v-for="line in platformLines"
                    :key="line.label"
                    class="rounded-2xl border border-stone-100 bg-stone-50 p-4 dark:border-stone-800 dark:bg-stone-950/40"
                >
                    <p class="text-xs font-bold text-stone-400">{{ line.label }}</p>
                    <p
                        :class="[
                            'mt-2 text-2xl font-black',
                            line.tone === 'bad' ? 'text-red-600' : line.tone === 'good' ? 'text-emerald-600' : 'text-stone-900 dark:text-white',
                        ]"
                    >
                        {{ money(line.value) }} <span class="text-xs font-bold text-stone-400">ج.م</span>
                    </p>
                    <p class="mt-2 text-[11px] leading-5 text-stone-500">{{ line.hint }}</p>
                </article>
            </div>
            <div v-if="expenses.length" class="mt-5 overflow-hidden rounded-2xl border border-stone-100 dark:border-stone-800">
                <div class="bg-stone-50 px-4 py-3 text-sm font-black dark:bg-stone-950/40">المصروفات حسب النوع</div>
                <div class="divide-y divide-stone-100 dark:divide-stone-800">
                    <div v-for="expense in expenses" :key="expense.name" class="flex items-center justify-between px-4 py-3 text-sm">
                        <span class="font-bold">{{ expense.name }}</span>
                        <span class="font-black text-red-600">{{ money(expense.amount) }} ج.م</span>
                    </div>
                </div>
            </div>
            <p v-else class="mt-4 text-sm font-bold text-stone-400">مفيش مصروفات متسجلة في الفترة دي.</p>
        </section>

        <section class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="rounded-2xl bg-orange-100 p-2 text-orange-700 dark:bg-orange-950 dark:text-orange-300">
                        <ClipboardList class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-lg font-black">2. الطلبات</h2>
                        <p class="text-xs text-stone-400">كل طلب اتعمل في الفترة، واقف فين دلوقتي.</p>
                    </div>
                </div>
                <div class="flex gap-2 text-xs font-black">
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">إتمام {{ orders.completion_rate }}%</span>
                    <span class="rounded-full bg-red-50 px-3 py-1 text-red-600 dark:bg-red-950/50 dark:text-red-300">إلغاء {{ orders.cancellation_rate }}%</span>
                </div>
            </div>
            <p class="mb-4 text-sm font-bold text-stone-500">إجمالي الطلبات: {{ orders.total }}</p>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <article
                    v-for="bucket in orders.buckets"
                    :key="bucket.key"
                    :class="['rounded-2xl border p-4', bucketClass(bucket.key)]"
                >
                    <p class="text-xs font-black">{{ bucket.label }}</p>
                    <p class="mt-2 text-3xl font-black">{{ bucket.count }}</p>
                    <p class="mt-1 text-xs font-bold">{{ money(bucket.amount) }} ج.م</p>
                    <p class="mt-2 text-[11px] leading-5 opacity-80">{{ bucket.hint }}</p>
                </article>
            </div>
        </section>

        <section class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
            <div class="mb-5 flex items-center gap-3">
                <span class="rounded-2xl bg-amber-100 p-2 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                    <CircleDollarSign class="h-5 w-5" />
                </span>
                <div>
                    <h2 class="text-lg font-black">3. تقسيم فلوس الطلبات المسلّمة</h2>
                    <p class="text-xs text-stone-400">
                        {{ delivered_money.orders }} طلب مسلّم. متوسط الطلب {{ money(delivered_money.average_order) }} ج.م.
                    </p>
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <article
                    v-for="line in orderLines"
                    :key="line.label"
                    class="rounded-2xl border border-stone-100 bg-stone-50 p-4 dark:border-stone-800 dark:bg-stone-950/40"
                >
                    <p class="text-xs font-bold text-stone-400">{{ line.label }}</p>
                    <p
                        :class="[
                            'mt-2 text-2xl font-black',
                            line.tone === 'bad' ? 'text-red-600' : line.tone === 'good' ? 'text-emerald-600' : 'text-stone-900 dark:text-white',
                        ]"
                    >
                        {{ money(line.value) }} <span class="text-xs font-bold text-stone-400">ج.م</span>
                    </p>
                    <p class="mt-2 text-[11px] leading-5 text-stone-500">{{ line.hint }}</p>
                </article>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
                <div class="mb-5 flex items-center gap-3">
                    <span class="rounded-2xl bg-stone-100 p-2 text-stone-700 dark:bg-stone-800 dark:text-stone-200">
                        <Banknote class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-lg font-black">4. طرق الدفع</h2>
                        <p class="text-xs text-stone-400">للطلبات المسلّمة بس.</p>
                    </div>
                </div>
                <div v-if="payments.length === 0" class="text-sm font-bold text-stone-400">مفيش طلبات مسلّمة في الفترة.</div>
                <div v-else class="space-y-4">
                    <div v-for="payment in payments" :key="payment.method">
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-black">{{ payment.label }}</span>
                            <span class="font-bold text-stone-500">{{ payment.count }} طلب · {{ money(payment.amount) }} ج.م</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-stone-100 dark:bg-stone-800">
                            <div
                                class="h-full rounded-full bg-orange-500"
                                :style="{ width: `${Math.max(6, (payment.amount / maxPayment) * 100)}%` }"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
                <div class="mb-5 flex items-center gap-3">
                    <span class="rounded-2xl bg-indigo-100 p-2 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                        <Users class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-lg font-black">5. العملاء</h2>
                        <p class="text-xs text-stone-400">مين طلب في الفترة، وحسابه جديد ولا قديم.</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <article class="rounded-2xl bg-stone-50 p-4 dark:bg-stone-950/40">
                        <p class="text-xs font-bold text-stone-400">حسابات مسجّلة</p>
                        <p class="mt-1 text-2xl font-black">{{ customers.registered }}</p>
                        <p class="mt-1 text-[11px] text-stone-500">كل العملاء على المنصة، مش الفترة بس.</p>
                    </article>
                    <article class="rounded-2xl bg-stone-50 p-4 dark:bg-stone-950/40">
                        <p class="text-xs font-bold text-stone-400">طلبوا في الفترة</p>
                        <p class="mt-1 text-2xl font-black">{{ customers.ordered }}</p>
                        <p class="mt-1 text-[11px] text-stone-500">عملاء مختلفين، حتى لو طلبوا أكتر من مرة.</p>
                    </article>
                    <article class="rounded-2xl bg-orange-50 p-4 dark:bg-orange-950/30">
                        <p class="text-xs font-bold text-orange-700 dark:text-orange-300">حساب جديد</p>
                        <p class="mt-1 text-2xl font-black">{{ customers.new }}</p>
                        <p class="mt-1 text-[11px] text-stone-500">حسابهم اتفتح في نفس الفترة وطلبوا.</p>
                    </article>
                    <article class="rounded-2xl bg-indigo-50 p-4 dark:bg-indigo-950/30">
                        <p class="text-xs font-bold text-indigo-700 dark:text-indigo-300">رجّع يطلب</p>
                        <p class="mt-1 text-2xl font-black">{{ customers.returning }}</p>
                        <p class="mt-1 text-[11px] text-stone-500">حسابهم أقدم من الفترة، وطلبوا فيها.</p>
                    </article>
                </div>
            </section>
        </div>

        <section class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
            <div class="mb-5 flex items-center gap-3">
                <span class="rounded-2xl bg-sky-100 p-2 text-sky-700 dark:bg-sky-950 dark:text-sky-300">
                    <Bike class="h-5 w-5" />
                </span>
                <div>
                    <h2 class="text-lg font-black">6. التوصيل</h2>
                    <p class="text-xs text-stone-400">الكباتن حالة حالية، والوقت والرسوم حسب الفترة.</p>
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <article class="rounded-2xl bg-stone-50 p-4 dark:bg-stone-950/40">
                    <p class="text-xs font-bold text-stone-400">كباتن مفعّلين</p>
                    <p class="mt-1 text-2xl font-black">{{ delivery.active_drivers }}</p>
                </article>
                <article class="rounded-2xl bg-stone-50 p-4 dark:bg-stone-950/40">
                    <p class="text-xs font-bold text-stone-400">متاحين دلوقتي</p>
                    <p class="mt-1 text-2xl font-black">{{ delivery.available_drivers }}</p>
                </article>
                <article class="rounded-2xl bg-stone-50 p-4 dark:bg-stone-950/40">
                    <p class="text-xs font-bold text-stone-400">طلبات مع الكابتن</p>
                    <p class="mt-1 text-2xl font-black">{{ delivery.on_the_way }}</p>
                </article>
                <article class="rounded-2xl bg-stone-50 p-4 dark:bg-stone-950/40">
                    <p class="text-xs font-bold text-stone-400">متوسط التوصيل</p>
                    <p class="mt-1 text-2xl font-black">{{ delivery.average_minutes }} <span class="text-xs">دقيقة</span></p>
                    <p class="mt-1 text-[11px] text-stone-500">رسوم التوصيل: {{ money(delivery.fees) }} ج.م</p>
                </article>
            </div>
        </section>

        <section class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
            <div class="mb-5 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="rounded-2xl bg-orange-100 p-2 text-orange-700 dark:bg-orange-950 dark:text-orange-300">
                        <Store class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-lg font-black">7. المطاعم</h2>
                        <p class="text-xs text-stone-400">كل مطعم لوحده: طلباته، مبيعاته، وعمولته، واللي لسه عليه.</p>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[920px] text-right text-sm">
                    <thead>
                        <tr class="border-b border-stone-100 text-xs font-bold text-stone-400 dark:border-stone-800">
                            <th class="px-3 py-3">المطعم</th>
                            <th class="px-3 py-3">الحالة</th>
                            <th class="px-3 py-3">الطلبات</th>
                            <th class="px-3 py-3">مسلّمة</th>
                            <th class="px-3 py-3">ملغية</th>
                            <th class="px-3 py-3">العميل دفع</th>
                            <th class="px-3 py-3">التوصيل</th>
                            <th class="px-3 py-3">عمولة وصلة</th>
                            <th class="px-3 py-3">صافي المطعم</th>
                            <th class="px-3 py-3">مستحق عليه</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        <tr v-for="restaurant in restaurants" :key="restaurant.id" class="hover:bg-stone-50 dark:hover:bg-stone-800/40">
                            <td class="px-3 py-3 font-black">
                                <Link :href="`/admin/finance/restaurants/${restaurant.id}`" class="text-orange-600 hover:underline">
                                    {{ restaurant.name }}
                                </Link>
                            </td>
                            <td class="px-3 py-3">{{ restaurantStatus(restaurant.status) }}</td>
                            <td class="px-3 py-3">{{ restaurant.total_orders }}</td>
                            <td class="px-3 py-3 text-emerald-600">{{ restaurant.delivered_orders }}</td>
                            <td class="px-3 py-3 text-red-600">{{ restaurant.cancelled_orders }}</td>
                            <td class="px-3 py-3">{{ money(restaurant.customer_paid) }}</td>
                            <td class="px-3 py-3">{{ money(restaurant.delivery_fees) }}</td>
                            <td class="px-3 py-3">{{ money(restaurant.platform_commission) }}</td>
                            <td class="px-3 py-3 font-black text-emerald-600">{{ money(restaurant.restaurant_net) }}</td>
                            <td class="px-3 py-3 font-black text-amber-600">{{ money(restaurant.unpaid_due) }}</td>
                        </tr>
                        <tr v-if="restaurants.length === 0">
                            <td colspan="10" class="px-3 py-8 text-center font-bold text-stone-400">مفيش مطاعم.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
                <div class="mb-5 flex items-center gap-3">
                    <span class="rounded-2xl bg-amber-100 p-2 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                        <CalendarRange class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-lg font-black">8. اليوم بيوم</h2>
                        <p class="text-xs text-stone-400">مبيعات الطلبات المسلّمة. في «كل الوقت» بنعرض آخر 30 يوم.</p>
                    </div>
                </div>
                <div class="flex h-44 items-end gap-1 border-b border-stone-100 dark:border-stone-800">
                    <div
                        v-for="day in daily"
                        :key="day.date"
                        class="group relative flex h-full flex-1 items-end"
                    >
                        <div class="pointer-events-none absolute -top-8 z-10 hidden rounded-lg bg-stone-950 px-2 py-1 text-[10px] font-bold whitespace-nowrap text-white group-hover:block">
                            {{ day.label }} · {{ money(day.revenue) }} ج.م · {{ day.delivered }} مسلّم
                        </div>
                        <div
                            class="w-full rounded-t-md bg-orange-400"
                            :style="{ height: `${Math.max(day.revenue > 0 ? 8 : 2, (day.revenue / maxDailyRevenue) * 100)}%` }"
                        />
                    </div>
                </div>
                <div class="mt-2 flex justify-between text-[11px] font-bold text-stone-400">
                    <span>{{ daily[0]?.label }}</span>
                    <span>{{ daily[daily.length - 1]?.label }}</span>
                </div>
            </section>

            <section class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
                <div class="mb-5 flex items-center gap-3">
                    <span class="rounded-2xl bg-emerald-100 p-2 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        <UtensilsCrossed class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-lg font-black">9. الأكل الأكثر طلبًا</h2>
                        <p class="text-xs text-stone-400">أعلى 10 أصناف من الطلبات المسلّمة.</p>
                    </div>
                </div>
                <div v-if="top_items.length === 0" class="text-sm font-bold text-stone-400">مفيش أصناف مباعة في الفترة.</div>
                <ol v-else class="space-y-3">
                    <li v-for="(item, index) in top_items" :key="`${item.restaurant}-${item.name}`" class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-stone-100 text-xs font-black dark:bg-stone-800">{{ index + 1 }}</span>
                            <div>
                                <p class="text-sm font-black">{{ item.name }}</p>
                                <p class="text-xs text-stone-400">{{ item.restaurant }} · {{ item.qty }} قطعة</p>
                            </div>
                        </div>
                        <p class="text-sm font-black text-emerald-600">{{ money(item.revenue) }} ج.م</p>
                    </li>
                </ol>
            </section>
        </div>

        <section class="flex flex-wrap gap-3 text-sm font-black">
            <Link href="/admin/finance" class="inline-flex items-center gap-2 rounded-2xl border border-stone-200 bg-white px-4 py-3 dark:border-stone-800 dark:bg-stone-900">
                <Receipt class="h-4 w-4 text-orange-500" />
                الأرباح والتدفقات
            </Link>
            <Link href="/admin/billing" class="inline-flex items-center gap-2 rounded-2xl border border-stone-200 bg-white px-4 py-3 dark:border-stone-800 dark:bg-stone-900">
                <Wallet class="h-4 w-4 text-orange-500" />
                مركز التحصيل
            </Link>
            <Link href="/admin/analytics" class="inline-flex items-center gap-2 rounded-2xl border border-stone-200 bg-white px-4 py-3 dark:border-stone-800 dark:bg-stone-900">
                <CalendarRange class="h-4 w-4 text-orange-500" />
                المؤشرات التفصيلية
            </Link>
        </section>
    </div>
</template>
