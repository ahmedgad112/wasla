<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DeliveryLayout from '../../Layouts/DeliveryLayout.vue';
import type { Order, PaginatedResponse } from '../../Types';
import {
    Bike,
    CheckCircle2,
    Clock,
    MapPin,
    ArrowRight,
    Calendar,
    Search,
    Store,
    Navigation,
    PackageCheck,
    AlertCircle,
} from '@lucide/vue';

const props = withDefaults(
    defineProps<{
        orders: PaginatedResponse<Order>;
        today_orders?: Order[];
        active_orders?: Order[];
        today_count?: number;
        today_earnings?: number;
        total_count?: number;
    }>(),
    {
        today_orders: () => [],
        active_orders: () => [],
        today_count: 0,
        today_earnings: 0,
        total_count: 0,
    },
);

const activeTab = ref<'TODAY' | 'ACTIVE' | 'ALL'>('TODAY');
const searchQuery = ref('');

const handleUpdateStatus = (orderId: number, nextStatus: 'OUT_FOR_DELIVERY' | 'DELIVERED'): void => {
    router.patch(`/delivery/orders/${orderId}/status`, {
        status: nextStatus,
    });
};

const displayedOrders = computed(() => {
    let list: Order[] = [];
    if (activeTab.value === 'TODAY') {
        list = props.today_orders;
    } else if (activeTab.value === 'ACTIVE') {
        list = props.active_orders;
    } else {
        list = props.orders?.data || [];
    }

    if (!searchQuery.value.trim()) {
        return list;
    }

    const q = searchQuery.value.toLowerCase();
    return list.filter(
        (order) =>
            order.order_number.toLowerCase().includes(q) ||
            order.restaurant?.name?.toLowerCase().includes(q) ||
            order.address.toLowerCase().includes(q) ||
            order.customer?.user?.name?.toLowerCase().includes(q),
    );
});

const statusMeta = (status: string) => {
    switch (status) {
        case 'DELIVERED':
            return { label: 'تم التسليم', class: 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300', icon: 'delivered' };
        case 'OUT_FOR_DELIVERY':
            return { label: 'في الطريق للعميل', class: 'bg-teal-100 dark:bg-teal-950 text-teal-700 dark:text-teal-300', icon: 'out' };
        case 'ASSIGNED_TO_DRIVER':
            return { label: 'تم التكليف (جاهز للاستلام)', class: 'bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300', icon: 'assigned' };
        case 'CANCELLED':
            return { label: 'ملغي', class: 'bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300', icon: 'cancelled' };
        default:
            return { label: status, class: 'bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300', icon: 'default' };
    }
};
</script>

<template>
    <DeliveryLayout title="سجل التوصيلات والطلبات">
        <Head title="سجل الطلبات" />

        <div class="space-y-6 pb-10" dir="rtl">
            <div class="bg-gradient-to-r from-emerald-700 via-teal-800 to-stone-900 text-white p-6 sm:p-7 rounded-3xl shadow-lg relative overflow-hidden">
                <div class="relative z-10">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md text-emerald-200 text-xs font-bold mb-2">
                        <Bike class="w-3.5 h-3.5" />
                        <span>سجل رحلات ومهام الكابتن اليومية</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black">
                        قائمة وسجل الطلبات بالكامل
                    </h1>
                    <p class="text-emerald-100 text-xs sm:text-sm mt-1 max-w-lg">
                        هنا تجد كل الطلبات التي استلمتها خلال اليوم وسجل رحلاتك السابقة مع تفاصيل المبالغ المحصلة.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="p-4 rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                    <span class="text-[11px] font-bold text-stone-400 block mb-1">طلبات تم توصيلها اليوم</span>
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ today_count }}</span>
                        <span class="text-xs text-stone-400">طلب</span>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                    <span class="text-[11px] font-bold text-stone-400 block mb-1">تحصيل كاش اليوم</span>
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ Number(today_earnings).toLocaleString() }}</span>
                        <span class="text-xs text-stone-400">ج.م</span>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                    <span class="text-[11px] font-bold text-stone-400 block mb-1">الطلبات الجارية الآن</span>
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl font-black text-purple-600 dark:text-purple-400">{{ active_orders.length }}</span>
                        <span class="text-xs text-stone-400">في يدك</span>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                    <span class="text-[11px] font-bold text-stone-400 block mb-1">إجمالي كل التوصيلات</span>
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl font-black text-stone-900 dark:text-white">{{ total_count }}</span>
                        <span class="text-xs text-stone-400">رحلة ناجحة</span>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-4 sm:p-6 shadow-xs space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-stone-100 dark:border-stone-800">
                    <div class="flex items-center gap-1.5 p-1 bg-stone-100 dark:bg-stone-800 rounded-2xl">
                        <button
                            :class="[
                                'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5',
                                activeTab === 'TODAY'
                                    ? 'bg-white dark:bg-stone-900 text-emerald-600 dark:text-emerald-400 shadow-xs'
                                    : 'text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-200',
                            ]"
                            @click="activeTab = 'TODAY'"
                        >
                            <Calendar class="w-3.5 h-3.5" />
                            <span>طلبات اليوم</span>
                            <span
                                :class="[
                                    'px-1.5 py-0.2 rounded-full text-[10px]',
                                    activeTab === 'TODAY'
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 font-black'
                                        : 'bg-stone-200 dark:bg-stone-700',
                                ]"
                            >
                                {{ today_orders.length }}
                            </span>
                        </button>

                        <button
                            :class="[
                                'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5',
                                activeTab === 'ACTIVE'
                                    ? 'bg-white dark:bg-stone-900 text-purple-600 dark:text-purple-400 shadow-xs'
                                    : 'text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-200',
                            ]"
                            @click="activeTab = 'ACTIVE'"
                        >
                            <Navigation class="w-3.5 h-3.5" />
                            <span>الطلبات الجارية</span>
                            <span
                                v-if="active_orders.length > 0"
                                class="px-1.5 py-0.2 rounded-full text-[10px] bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300 font-black animate-pulse"
                            >
                                {{ active_orders.length }}
                            </span>
                        </button>

                        <button
                            :class="[
                                'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5',
                                activeTab === 'ALL'
                                    ? 'bg-white dark:bg-stone-900 text-stone-900 dark:text-white shadow-xs'
                                    : 'text-stone-500 dark:text-stone-400 hover:text-stone-800 dark:hover:text-stone-200',
                            ]"
                            @click="activeTab = 'ALL'"
                        >
                            <PackageCheck class="w-3.5 h-3.5" />
                            <span>كل السجل</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-stone-200 dark:bg-stone-700">
                                {{ orders?.total || 0 }}
                            </span>
                        </button>
                    </div>

                    <div class="relative min-w-[240px]">
                        <Search class="w-4 h-4 text-stone-400 absolute right-3.5 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="بحث برقم الطلب، المطعم، أو العنوان..."
                            class="w-full pr-10 pl-3 py-2 rounded-xl border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 text-xs text-stone-900 dark:text-stone-100 focus:outline-hidden focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>
                </div>

                <div v-if="displayedOrders.length === 0" class="text-center py-16">
                    <Bike class="w-14 h-14 text-stone-300 dark:text-stone-600 mx-auto mb-3" />
                    <h3 class="text-sm font-bold text-stone-700 dark:text-stone-300">
                        <template v-if="activeTab === 'ACTIVE'">لا توجد طلبات جارية في يدك الآن.</template>
                        <template v-else-if="activeTab === 'TODAY'">لم يتم تسجيل طلبات جديدة لك اليوم بعد.</template>
                        <template v-else>لا توجد طلبات في هذا السجل.</template>
                    </h3>
                    <p v-if="activeTab === 'ACTIVE'" class="text-xs text-stone-400 mt-1">
                        عندما يسند إليك المطعم طلباً جديداً ستجده هنا فوراً.
                    </p>
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-for="order in displayedOrders"
                        :key="order.id"
                        :class="[
                            'p-4 sm:p-5 rounded-2xl border transition-all',
                            order.status === 'ASSIGNED_TO_DRIVER' || order.status === 'OUT_FOR_DELIVERY'
                                ? 'bg-purple-50/40 dark:bg-purple-950/20 border-purple-300 dark:border-purple-800/80 shadow-xs'
                                : order.status === 'DELIVERED'
                                  ? 'bg-white dark:bg-stone-900/60 border-stone-200 dark:border-stone-800 hover:border-emerald-300 dark:hover:border-emerald-800'
                                  : 'bg-white dark:bg-stone-900 border-stone-200 dark:border-stone-800',
                        ]"
                    >
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-stone-100 dark:border-stone-800">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                                    <Bike class="w-5 h-5" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-black text-emerald-600 dark:text-emerald-400">
                                            {{ order.order_number }}
                                        </span>
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold',
                                                statusMeta(order.status).class,
                                            ]"
                                        >
                                            <CheckCircle2 v-if="statusMeta(order.status).icon === 'delivered'" class="w-3.5 h-3.5" />
                                            <Navigation v-else-if="statusMeta(order.status).icon === 'out'" class="w-3.5 h-3.5" />
                                            <Clock v-else-if="statusMeta(order.status).icon === 'assigned'" class="w-3.5 h-3.5" />
                                            <AlertCircle v-else-if="statusMeta(order.status).icon === 'cancelled'" class="w-3.5 h-3.5" />
                                            {{ statusMeta(order.status).label }}
                                        </span>
                                    </div>
                                    <p class="text-xs font-bold text-stone-800 dark:text-stone-200 mt-0.5">
                                        مطعم: {{ order.restaurant?.name || 'مطعم وصلة' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between sm:justify-end gap-4">
                                <div class="text-right sm:text-left">
                                    <span class="text-[10px] text-stone-400 block font-medium">المبلغ المطلوب تحصيله كاش</span>
                                    <span class="font-black text-base text-stone-900 dark:text-white">
                                        {{ Number(order.total_amount).toLocaleString() }} ج.م
                                    </span>
                                </div>

                                <Link
                                    :href="`/delivery/orders/${order.id}`"
                                    class="px-3.5 py-2 rounded-xl border border-stone-200 dark:border-stone-700 text-xs font-bold text-stone-700 dark:text-stone-300 hover:bg-stone-50 dark:hover:bg-stone-800 transition flex items-center gap-1"
                                >
                                    <span>التفاصيل</span>
                                    <ArrowRight class="w-3.5 h-3.5 rotate-180" />
                                </Link>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-3 text-xs">
                            <div class="flex items-start gap-2 text-stone-600 dark:text-stone-300">
                                <Store class="w-4 h-4 text-orange-500 shrink-0 mt-0.5" />
                                <div>
                                    <span class="font-bold block text-stone-700 dark:text-stone-200">الاستلام:</span>
                                    <span class="text-stone-500 dark:text-stone-400 text-[11px]">
                                        {{ order.restaurant?.address || 'عنوان المطعم الرئيسي' }}
                                    </span>
                                    <a
                                        v-if="order.restaurant?.phone"
                                        :href="`tel:${order.restaurant.phone}`"
                                        class="text-orange-600 block text-[11px] font-bold hover:underline mt-0.5"
                                    >
                                        هاتف المطعم: {{ order.restaurant.phone }}
                                    </a>
                                </div>
                            </div>

                            <div class="flex items-start gap-2 text-stone-600 dark:text-stone-300">
                                <MapPin class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                                <div>
                                    <span class="font-bold block text-stone-700 dark:text-stone-200">التسليم للعميل:</span>
                                    <span class="text-stone-700 dark:text-stone-300 text-[11px] font-medium">
                                        {{ order.address }}
                                    </span>
                                    <a
                                        v-if="order.customer?.user?.phone"
                                        :href="`tel:${order.customer.user.phone}`"
                                        class="text-emerald-600 block text-[11px] font-bold hover:underline mt-0.5"
                                    >
                                        هاتف العميل: {{ order.customer.user.phone }} ({{ order.customer?.user?.name }})
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="order.status === 'ASSIGNED_TO_DRIVER' || order.status === 'OUT_FOR_DELIVERY'"
                            class="mt-4 pt-3 border-t border-purple-200 dark:border-purple-900/50 flex flex-wrap items-center justify-between gap-2"
                        >
                            <span class="text-[11px] font-bold text-purple-700 dark:text-purple-300 flex items-center gap-1">
                                <Clock class="w-3.5 h-3.5" />
                                هذا الطلب مكلف به حالياً
                            </span>

                            <div class="flex items-center gap-2">
                                <button
                                    v-if="order.status === 'ASSIGNED_TO_DRIVER'"
                                    class="px-3.5 py-1.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold transition shadow-xs"
                                    @click="handleUpdateStatus(order.id, 'OUT_FOR_DELIVERY')"
                                >
                                    استلمت من المطعم وفي الطريق
                                </button>

                                <button
                                    v-if="order.status === 'OUT_FOR_DELIVERY'"
                                    class="px-4 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-xs flex items-center gap-1"
                                    @click="handleUpdateStatus(order.id, 'DELIVERED')"
                                >
                                    <CheckCircle2 class="w-3.5 h-3.5" />
                                    <span>تم التسليم وتحصيل الكاش</span>
                                </button>
                            </div>
                        </div>

                        <div class="mt-2 text-[10px] text-stone-400 flex items-center justify-between">
                            <span>
                                تاريخ الطلب: {{ new Date(order.created_at).toLocaleString('ar-EG') }}
                            </span>
                            <span v-if="order.items && order.items.length > 0">
                                {{ order.items.length }} أصناف في الوجبة
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    v-if="activeTab === 'ALL' && orders?.links && orders.links.length > 3"
                    class="pt-4 border-t border-stone-100 dark:border-stone-800 flex items-center justify-center gap-1"
                >
                    <Link
                        v-for="(link, idx) in orders.links"
                        :key="idx"
                        :href="link.url || '#'"
                        preserve-scroll
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold transition',
                            link.active
                                ? 'bg-emerald-600 text-white'
                                : !link.url
                                  ? 'text-stone-300 dark:text-stone-600 cursor-not-allowed'
                                  : 'text-stone-600 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-stone-800',
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </DeliveryLayout>
</template>
