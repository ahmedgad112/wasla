<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import type { Restaurant, Order, PaginatedResponse } from '../../../Types';
import {
    ShoppingBag,
    Eye,
    CheckCircle2,
    Clock,
    Search,
    Phone,
    Bike,
    Flame,
    PackageCheck,
    Navigation,
    AlertTriangle,
    User,
    MapPin,
    DollarSign,
    X,
    UserCheck,
    RefreshCw,
} from '@lucide/vue';

const props = withDefaults(
    defineProps<{
        orders: PaginatedResponse<Order>;
        restaurant: Restaurant;
        filters: { status?: string; search?: string };
        counts: {
            all: number;
            pending: number;
            confirmed: number;
            preparing: number;
            ready_for_pickup: number;
            out_for_delivery: number;
            delivered: number;
            cancelled: number;
            today_orders: number;
            today_revenue: number;
        };
        available_drivers?: { id: number; name: string; phone: string; restaurant_id?: number | null }[];
    }>(),
    {
        available_drivers: () => [],
    },
    
);

const items = computed(() => props.orders?.data || []);
const searchTerm = ref(props.filters.search || '');
const assigningOrderId = ref<number | null>(null);
const selectedDriverId = ref<number | string>('');

const handleFilterStatus = (status: string): void => {
    router.get(
        '/restaurant/orders',
        {
            status: status === 'ALL' ? '' : status,
            search: searchTerm.value || undefined,
        },
        { preserveState: true },
    );
};

const handleSearch = (): void => {
    router.get(
        '/restaurant/orders',
        {
            status: props.filters.status || undefined,
            search: searchTerm.value || undefined,
        },
        { preserveState: true },
    );
};

const handleAdvanceStatus = (orderId: number, status: string): void => {
    router.patch(`/restaurant/orders/${orderId}/status`, { status }, { preserveScroll: true });
};

const handleAssignDriver = (orderId: number): void => {
    if (!selectedDriverId.value) {
        return;
    }
    router.post(
        `/restaurant/orders/${orderId}/assign-driver`,
        { driver_id: selectedDriverId.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                assigningOrderId.value = null;
                selectedDriverId.value = '';
            },
        },
    );
};

const statuses = computed(() => [
    { key: 'ALL', label: 'جميع الطلبات', count: props.counts?.all || 0 },
    { key: 'PENDING', label: 'بانتظار الموافقة', count: props.counts?.pending || 0 },
    { key: 'CONFIRMED', label: 'مؤكد', count: props.counts?.confirmed || 0 },
    { key: 'PREPARING', label: 'قيد التجهيز بالمطبخ', count: props.counts?.preparing || 0 },
    { key: 'READY_FOR_PICKUP', label: 'جاهز للاستلام', count: props.counts?.ready_for_pickup || 0 },
    { key: 'OUT_FOR_DELIVERY', label: 'مع الطيار بالشارع', count: props.counts?.out_for_delivery || 0 },
    { key: 'DELIVERED', label: 'تم التسليم بنجاح', count: props.counts?.delivered || 0 },
    { key: 'CANCELLED', label: 'ملغي أو مرفوض', count: props.counts?.cancelled || 0 },
]);

const getStatusDetails = (status: string) => {
    switch (status) {
        case 'PENDING':
            return {
                label: 'بانتظار قبول المطعم',
                bg: 'bg-amber-100 dark:bg-amber-950/70 border-amber-300 dark:border-amber-800 text-amber-800 dark:text-amber-300',
                dot: 'bg-amber-500',
                icon: 'Clock',
            };
        case 'CONFIRMED':
            return {
                label: 'تم التأكيد',
                bg: 'bg-blue-100 dark:bg-blue-950/70 border-blue-300 dark:border-blue-800 text-blue-800 dark:text-blue-300',
                dot: 'bg-blue-500',
                icon: 'CheckCircle2',
            };
        case 'PREPARING':
            return {
                label: 'قيد الطهي والتجهيز',
                bg: 'bg-orange-100 dark:bg-orange-950/70 border-orange-300 dark:border-orange-800 text-orange-800 dark:text-orange-300',
                dot: 'bg-orange-500',
                icon: 'Flame',
            };
        case 'READY_FOR_PICKUP':
            return {
                label: 'جاهز بانتظار الطيار',
                bg: 'bg-purple-100 dark:bg-purple-950/70 border-purple-300 dark:border-purple-800 text-purple-800 dark:text-purple-300',
                dot: 'bg-purple-500',
                icon: 'PackageCheck',
            };
        case 'OUT_FOR_DELIVERY':
            return {
                label: 'في الطريق للعميل',
                bg: 'bg-teal-100 dark:bg-teal-950/70 border-teal-300 dark:border-teal-800 text-teal-800 dark:text-teal-300',
                dot: 'bg-teal-500',
                icon: 'Navigation',
            };
        case 'DELIVERED':
            return {
                label: 'تم التسليم بنجاح',
                bg: 'bg-emerald-100 dark:bg-emerald-950/70 border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300',
                dot: 'bg-emerald-500',
                icon: 'CheckCircle2',
            };
        case 'CANCELLED':
        case 'REJECTED':
            return {
                label: 'طلب ملغي',
                bg: 'bg-red-100 dark:bg-red-950/70 border-red-300 dark:border-red-900 text-red-800 dark:text-red-300',
                dot: 'bg-red-500',
                icon: 'AlertTriangle',
            };
        default:
            return {
                label: status,
                bg: 'bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300',
                dot: 'bg-stone-400',
                icon: 'Clock',
            };
    }
};

const orderDriver = (order: Order) => order.delivery_driver || order.deliveryDriver;
</script>

<template>
    <Head title="إدارة الطلبات الواردة — بوابة المطعم" />

    <div class="space-y-6 pb-12" dir="rtl">
        <div class="relative overflow-hidden p-6 sm:p-7 rounded-3xl bg-gradient-to-r from-orange-600 via-amber-600 to-orange-500 text-white shadow-xl">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-bold mb-2">
                        <ShoppingBag class="w-3.5 h-3.5 text-amber-200" />
                        <span>لوحة إدارة حركة الطلبات الحية</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black">
                        إدارة ومتابعة الطلبات الواردة
                    </h1>
                    <p class="text-orange-100 text-xs sm:text-sm mt-1 max-w-xl">
                        متابعة فورية لطلبات الزبائن، مراحل التحضير في المطبخ، وتعيين كباتن التوصيل لفرع {{ restaurant.name }}.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        class="px-4 py-2.5 rounded-2xl bg-white/10 hover:bg-white/20 text-stone-900 font-bold text-xs backdrop-blur-md transition flex items-center gap-1.5 shadow-xs"
                        @click="router.reload({ preserveUrl: true })"
                    >
                        <RefreshCw class="w-4 h-4" />
                        <span>تحديث الطلبات</span>
                    </button>
                    <Link
                        href="/restaurant/driver-stats"
                        class="px-4 py-2.5 rounded-2xl bg-white text-orange-600 hover:bg-orange-50 font-black text-xs shadow-md transition flex items-center gap-1.5"
                    >
                        <Bike class="w-4 h-4" />
                        <span>إحصائيات الكباتن</span>
                    </Link>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div
                :class="[
                    'p-5 rounded-3xl border transition shadow-xs',
                    (counts?.pending || 0) > 0
                        ? 'bg-amber-500/10 border-amber-300 dark:border-amber-800 ring-2 ring-amber-500/20'
                        : 'bg-white dark:bg-stone-900 border-stone-200 dark:border-stone-800',
                ]"
            >
                <div class="flex items-center justify-between text-xs text-stone-400 font-bold mb-2">
                    <span class="text-amber-700 dark:text-amber-400">بانتظار الموافقة الآن</span>
                    <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-600 flex items-center justify-center">
                        <Clock class="w-4 h-4" />
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-black text-amber-600 dark:text-amber-400">
                        {{ counts?.pending || 0 }}
                    </span>
                    <span class="text-xs text-stone-400">طلب جديد</span>
                </div>
                <p v-if="(counts?.pending || 0) > 0" class="text-[11px] text-amber-600 font-bold mt-1 animate-pulse">
                    يوجد طلبات جديدة تحتاج موافقتك فوراً!
                </p>
            </div>

            <div class="p-5 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                <div class="flex items-center justify-between text-xs text-stone-400 font-bold mb-2">
                    <span>قيد الطهي والتجهيز</span>
                    <div class="w-8 h-8 rounded-xl bg-orange-500/10 text-orange-600 flex items-center justify-center">
                        <Flame class="w-4 h-4" />
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-black text-orange-600 dark:text-orange-400">
                        {{ counts?.preparing || 0 }}
                    </span>
                    <span class="text-xs text-stone-400">وجبة على النار</span>
                </div>
                <p class="text-[11px] text-stone-400 mt-1">يتم تحضيرها في المطبخ</p>
            </div>

            <div class="p-5 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                <div class="flex items-center justify-between text-xs text-stone-400 font-bold mb-2">
                    <span>جاهز بانتظار الطيار</span>
                    <div class="w-8 h-8 rounded-xl bg-purple-500/10 text-purple-600 flex items-center justify-center">
                        <PackageCheck class="w-4 h-4" />
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-black text-purple-600 dark:text-purple-400">
                        {{ counts?.ready_for_pickup || 0 }}
                    </span>
                    <span class="text-xs text-stone-400">طلب مغلف</span>
                </div>
                <p class="text-[11px] text-stone-400 mt-1">جاهزة ومغلفة للاستلام</p>
            </div>

            <div class="p-5 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                <div class="flex items-center justify-between text-xs text-stone-400 font-bold mb-2">
                    <span>مبيعات اليوم المحصلة</span>
                    <div class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                        <DollarSign class="w-4 h-4" />
                    </div>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400">
                        {{ Number(counts?.today_revenue || 0).toLocaleString() }}
                    </span>
                    <span class="text-xs text-stone-400 mr-1">ج.م</span>
                </div>
                <p class="text-[11px] text-stone-400 mt-1">
                    من إجمالي {{ counts?.today_orders || 0 }} طلب اليوم
                </p>
            </div>
        </div>

        <div class="bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-5 sm:p-6 shadow-xs space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-stone-100 dark:border-stone-800">
                <form class="relative flex-1 max-w-md" @submit.prevent="handleSearch">
                    <Search class="w-4 h-4 text-stone-400 absolute right-3.5 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="searchTerm"
                        type="text"
                        placeholder="بحث برقم الطلب، اسم العميل، الهاتف، أو العنوان..."
                        class="w-full pr-10 pl-24 py-2 rounded-xl border border-stone-200 dark:border-stone-700 bg-stone-50 dark:bg-stone-800 text-xs text-stone-900 dark:text-stone-100 focus:outline-hidden focus:ring-2 focus:ring-orange-500"
                    />
                    <button
                        type="submit"
                        class="absolute left-1.5 top-1.5 bottom-1.5 px-3 rounded-lg bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs transition"
                    >
                        بحث
                    </button>
                </form>
            </div>

            <div class="flex items-center gap-1.5 overflow-x-auto pb-2 custom-scrollbar">
                <button
                    v-for="st in statuses"
                    :key="st.key"
                    :class="[
                        'px-3.5 py-2 rounded-2xl text-xs font-bold whitespace-nowrap transition flex items-center gap-2',
                        filters.status === st.key || (!filters.status && st.key === 'ALL')
                            ? 'bg-orange-600 text-white shadow-sm'
                            : 'bg-stone-100 dark:bg-stone-800/80 text-stone-600 dark:text-stone-400 hover:bg-stone-200 dark:hover:bg-stone-700',
                    ]"
                    @click="handleFilterStatus(st.key)"
                >
                    <span>{{ st.label }}</span>
                    <span
                        :class="[
                            'px-2 py-0.5 rounded-full text-[10px] font-black',
                            filters.status === st.key || (!filters.status && st.key === 'ALL')
                                ? 'bg-white/20 text-white'
                                : 'bg-stone-200 dark:bg-stone-700 text-stone-700 dark:text-stone-300',
                        ]"
                    >
                        {{ st.count }}
                    </span>
                </button>
            </div>
        </div>

        <div v-if="items.length === 0" class="text-center py-20 bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-8">
            <ShoppingBag class="w-16 h-16 text-stone-300 dark:text-stone-600 mx-auto mb-3" />
            <h3 class="text-base font-bold text-stone-800 dark:text-stone-200 mb-1">
                لا توجد طلبات في هذا القسم
            </h3>
            <p class="text-xs text-stone-400">
                {{ searchTerm ? 'جرب البحث بكلمة أخرى أو مسح شريط البحث.' : 'أي طلب جديد يدخل من الزبائن سيظهر هنا مباشرة.' }}
            </p>
        </div>

        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div
                v-for="order in items"
                :key="order.id"
                class="bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-5 sm:p-6 shadow-xs hover:shadow-md transition-all flex flex-col justify-between space-y-4"
            >
                <div>
                    <div class="flex items-center justify-between gap-2 pb-3 border-b border-stone-100 dark:border-stone-800">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-black text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-950/60 px-2.5 py-1 rounded-xl border border-orange-200/60 dark:border-orange-900/40">
                                {{ order.order_number }}
                            </span>
                            <span class="text-[11px] text-stone-400">
                                منذ {{ new Date(order.created_at).toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' }) }}
                            </span>
                        </div>

                        <span :class="['inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black border', getStatusDetails(order.status).bg]">
                            <span :class="['w-2 h-2 rounded-full animate-pulse', getStatusDetails(order.status).dot]" />
                            <Clock v-if="getStatusDetails(order.status).icon === 'Clock'" class="w-3.5 h-3.5" />
                            <CheckCircle2 v-else-if="getStatusDetails(order.status).icon === 'CheckCircle2'" class="w-3.5 h-3.5" />
                            <Flame v-else-if="getStatusDetails(order.status).icon === 'Flame'" class="w-3.5 h-3.5" />
                            <PackageCheck v-else-if="getStatusDetails(order.status).icon === 'PackageCheck'" class="w-3.5 h-3.5" />
                            <Navigation v-else-if="getStatusDetails(order.status).icon === 'Navigation'" class="w-3.5 h-3.5" />
                            <AlertTriangle v-else-if="getStatusDetails(order.status).icon === 'AlertTriangle'" class="w-3.5 h-3.5" />
                            <span>{{ getStatusDetails(order.status).label }}</span>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 my-3 p-3.5 rounded-2xl bg-stone-50 dark:bg-stone-800/50 border border-stone-100 dark:border-stone-800 text-xs">
                        <div class="space-y-1">
                            <div class="flex items-center gap-1.5 font-bold text-stone-800 dark:text-stone-200">
                                <User class="w-3.5 h-3.5 text-stone-400" />
                                <span>العميل: {{ order.customer?.user?.name || 'مستخدم بدون اسم' }}</span>
                            </div>
                            <a
                                v-if="order.customer?.user?.phone"
                                :href="`tel:${order.customer.user.phone}`"
                                class="text-orange-600 dark:text-orange-400 font-bold hover:underline flex items-center gap-1 text-[11px]"
                            >
                                <Phone class="w-3 h-3" />
                                <span>{{ order.customer.user.phone }}</span>
                            </a>
                            <p class="text-[11px] text-stone-500 flex items-start gap-1">
                                <MapPin class="w-3 h-3 text-stone-400 shrink-0 mt-0.5" />
                                <span class="line-clamp-2">{{ order.address }}</span>
                            </p>
                        </div>

                        <div class="space-y-1 border-t sm:border-t-0 sm:border-r border-stone-200 dark:border-stone-700 pt-2 sm:pt-0 sm:pr-3">
                            <div class="flex items-center gap-1.5 font-bold text-stone-800 dark:text-stone-200">
                                <Bike class="w-3.5 h-3.5 text-emerald-500" />
                                <span>كابتن التوصيل:</span>
                            </div>
                            <div v-if="orderDriver(order)" class="space-y-0.5">
                                <span class="font-extrabold text-stone-900 dark:text-white block">
                                    {{ orderDriver(order)?.name }}
                                </span>
                                <a
                                    v-if="orderDriver(order)?.phone"
                                    :href="`tel:${orderDriver(order)?.phone}`"
                                    class="text-emerald-600 dark:text-emerald-400 font-bold hover:underline flex items-center gap-1 text-[11px]"
                                >
                                    <Phone class="w-3 h-3" />
                                    <span>{{ orderDriver(order)?.phone }}</span>
                                </a>
                            </div>
                            <div v-else class="pt-0.5">
                                <span class="text-[11px] text-stone-400 block mb-1">
                                    لم يُعين كابتن بعد
                                </span>
                                <button
                                    type="button"
                                    class="px-2.5 py-1 rounded-xl bg-purple-50 dark:bg-purple-950/60 border border-purple-200 dark:border-purple-800 text-purple-700 dark:text-purple-300 hover:bg-purple-100 text-[11px] font-bold transition flex items-center gap-1"
                                    @click="assigningOrderId = order.id"
                                >
                                    <UserCheck class="w-3 h-3" />
                                    <span>تعيين كابتن الآن</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="assigningOrderId === order.id"
                        class="p-3 mb-3 rounded-2xl bg-purple-50/80 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-900 space-y-2 animate-fade-in"
                    >
                        <div class="flex items-center justify-between text-xs font-bold text-purple-900 dark:text-purple-200">
                            <span>اختر كابتن التوصيل للطلب {{ order.order_number }}:</span>
                            <button class="text-stone-400 hover:text-stone-600" @click="assigningOrderId = null">
                                <X class="w-3.5 h-3.5" />
                            </button>
                        </div>
                        <div class="flex items-center gap-2">
                            <select
                                v-model="selectedDriverId"
                                class="flex-1 py-1.5 px-3 rounded-xl border border-purple-300 dark:border-purple-800 bg-white dark:bg-stone-900 text-xs text-stone-900 dark:text-stone-100 font-bold"
                            >
                                <option value="">-- اختر من الكباتن المتاحين --</option>
                                <option v-for="d in available_drivers" :key="d.id" :value="d.id">
                                    {{ d.name }} — {{ d.restaurant_id ? 'المطعم' : 'الموقع' }} ({{ d.phone }})
                                </option>
                            </select>
                            <button
                                type="button"
                                class="px-3.5 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs shadow-xs transition"
                                @click="handleAssignDriver(order.id)"
                            >
                                تأكيد التعيين
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1.5 mb-2">
                        <span class="text-[11px] font-bold text-stone-400 block">
                            الأصناف المطلوبة ({{ order.items?.length || 0 }}):
                        </span>
                        <div class="space-y-1 max-h-32 overflow-y-auto custom-scrollbar">
                            <div
                                v-for="item in order.items"
                                :key="item.id"
                                class="flex items-center justify-between text-xs py-1 border-b border-stone-50 dark:border-stone-800/60 last:border-0"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-md bg-orange-100 dark:bg-orange-950 text-orange-600 font-black text-[10px] flex items-center justify-center">
                                        {{ item.quantity }}×
                                    </span>
                                    <span class="font-bold text-stone-800 dark:text-stone-200">
                                        {{ item.name }}
                                    </span>
                                </div>
                                <span class="font-bold text-stone-600 dark:text-stone-400">
                                    {{ Number(item.total_price).toLocaleString() }} ج.م
                                </span>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="order.customer_notes"
                        class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/50 text-xs text-amber-800 dark:text-amber-300 mb-2"
                    >
                        <span class="font-bold block text-[10px]">ملاحظات خاصة من العميل:</span>
                        <p class="italic">{{ order.customer_notes }}</p>
                    </div>
                </div>

                <div class="pt-3 border-t border-stone-100 dark:border-stone-800 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <span class="text-[10px] text-stone-400 block">المبلغ المطلوب</span>
                        <span class="text-lg font-black text-stone-900 dark:text-white">
                            {{ Number(order.total_amount).toLocaleString() }} ج.م
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            v-if="order.status === 'PENDING'"
                            class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs shadow-md transition flex items-center gap-1.5"
                            @click="handleAdvanceStatus(order.id, 'CONFIRMED')"
                        >
                            <CheckCircle2 class="w-3.5 h-3.5" />
                            <span>قبول وتأكيد</span>
                        </button>

                        <button
                            v-if="order.status === 'CONFIRMED'"
                            class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-black text-xs shadow-md transition flex items-center gap-1.5"
                            @click="handleAdvanceStatus(order.id, 'PREPARING')"
                        >
                            <Flame class="w-3.5 h-3.5" />
                            <span>بدء التجهيز بالمطبخ</span>
                        </button>

                        <button
                            v-if="order.status === 'PREPARING'"
                            class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-black text-xs shadow-md transition flex items-center gap-1.5"
                            @click="handleAdvanceStatus(order.id, 'READY_FOR_PICKUP')"
                        >
                            <PackageCheck class="w-3.5 h-3.5" />
                            <span>الوجبة جاهزة</span>
                        </button>

                        <Link
                            :href="`/restaurant/orders/${order.id}`"
                            class="px-3.5 py-2 rounded-xl border border-stone-200 dark:border-stone-700 hover:bg-stone-100 dark:hover:bg-stone-800 text-stone-700 dark:text-stone-300 font-bold text-xs transition flex items-center gap-1"
                        >
                            <Eye class="w-3.5 h-3.5" />
                            <span>تفاصيل</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>


        <div
            v-if="orders?.links && orders.links.length > 3"
            class="pt-6 flex items-center justify-center gap-1.5"
        >
            <Link
                v-for="(link, idx) in orders.links"
                :key="idx"
                :href="link.url || '#'"
                preserve-scroll
                :class="[
                    'px-3.5 py-2 rounded-xl text-xs font-bold transition',
                    link.active
                        ? 'bg-orange-600 text-white shadow-xs'
                        : !link.url
                          ? 'text-stone-300 dark:text-stone-600 cursor-not-allowed'
                          : 'text-stone-600 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-stone-800 bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800',
                ]"
                v-html="link.label"
            />
        </div>
    </div>
</template>
