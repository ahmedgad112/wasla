<script setup lang="ts">
import { ref, computed, type Component } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    MapPin,
    Phone,
    Mail,
    Clock,
    DollarSign,
    Users,
    ShoppingBag,
    Edit,
    ArrowLeft,
    Ban,
    CheckCircle,
    Star,
    TrendingUp,
    Calendar,
    Percent,
} from '@lucide/vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';

interface Restaurant {
    id: number;
    name: string;
    slug: string;
    description: string;
    logo: string | null;
    cover_image: string | null;
    address: string;
    phone: string;
    email: string | null;
    status: string;
    opening_time: string;
    closing_time: string;
    delivery_fee: number;
    minimum_order_amount: number;
    estimated_delivery_time: number;
    commission_rate: number;
    tax_rate: number;
    payment_method: string;
    created_at: string;
    owner?: { id: number; name: string; email: string };
    staff_count?: number;
    orders_count?: number;
    menu_items_count?: number;
}

const props = defineProps<{
    restaurant: Restaurant;
    stats: {
        total_orders: number;
        completed_orders: number;
        total_revenue: number;
        platform_commission: number;
        avg_order_value: number;
        active_menu_items: number;
    };
    recentOrders: Array<{
        id: number;
        order_number: string;
        total_amount: number;
        created_at: string;
    }>;
}>();

const suspending = ref(false);
const confirmSuspend = ref(false);

const handleSuspend = (): void => {
    confirmSuspend.value = true;
};

const doSuspend = (): void => {
    confirmSuspend.value = false;
    suspending.value = true;
    router.post(
        `/admin/restaurants/${props.restaurant.id}/suspend`,
        {},
        {
            onFinish: () => {
                suspending.value = false;
            },
        },
    );
};

const handleActivate = (): void => {
    router.post(`/admin/restaurants/${props.restaurant.id}/activate`);
};

const statusConfig: Record<string, { label: string; cls: string }> = {
    ACTIVE: { label: 'نشط', cls: 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' },
    SUSPENDED: { label: 'معلق', cls: 'bg-red-500/20 text-red-400 border border-red-500/30' },
    PENDING: { label: 'قيد المراجعة', cls: 'bg-amber-500/20 text-amber-400 border border-amber-500/30' },
    INACTIVE: { label: 'غير نشط', cls: 'bg-stone-500/20 text-stone-400 border border-stone-500/30' },
};

const sc = computed(() => statusConfig[props.restaurant.status] ?? statusConfig.INACTIVE);

const statCards = computed(() => [
    { label: 'إجمالي الطلبات', value: props.stats.total_orders, icon: ShoppingBag, color: 'text-indigo-400' },
    { label: 'الطلبات المكتملة', value: props.stats.completed_orders, icon: CheckCircle, color: 'text-emerald-400' },
    { label: 'إجمالي الإيرادات', value: `${(props.stats.total_revenue / 100).toFixed(2)} ج`, icon: DollarSign, color: 'text-amber-400' },
    { label: 'عمولة المنصة', value: `${(props.stats.platform_commission / 100).toFixed(2)} ج`, icon: Percent, color: 'text-orange-400' },
    { label: 'متوسط الطلب', value: `${(props.stats.avg_order_value / 100).toFixed(2)} ج`, icon: TrendingUp, color: 'text-purple-400' },
    { label: 'عناصر القائمة', value: props.stats.active_menu_items, icon: Star, color: 'text-pink-400' },
]);

const infoRows = computed(() => [
    { icon: Phone, label: 'الهاتف', value: props.restaurant.phone },
    { icon: Mail, label: 'البريد الإلكتروني', value: props.restaurant.email ?? '—' },
    { icon: MapPin, label: 'العنوان', value: props.restaurant.address },
    { icon: Clock, label: 'أوقات العمل', value: `${props.restaurant.opening_time} — ${props.restaurant.closing_time}` },
    { icon: DollarSign, label: 'رسوم التوصيل', value: `${(props.restaurant.delivery_fee / 100).toFixed(2)} ج` },
    { icon: ShoppingBag, label: 'الحد الأدنى للطلب', value: `${(props.restaurant.minimum_order_amount / 100).toFixed(2)} ج` },
    { icon: Clock, label: 'وقت التوصيل', value: `${props.restaurant.estimated_delivery_time} دقيقة` },
    { icon: Calendar, label: 'تاريخ الانضمام', value: new Date(props.restaurant.created_at).toLocaleDateString('ar-EG') },
]);

const formatDate = (value: string): string => new Date(value).toLocaleDateString('ar-EG');
</script>

<template>
    <Head :title="`${restaurant.name} — المطاعم`" />

    <div class="space-y-6" dir="rtl">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link
                    href="/admin/restaurants"
                    class="p-2 rounded-lg bg-white hover:bg-stone-100 transition-colors text-stone-400 hover:text-stone-900"
                >
                    <ArrowLeft class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-stone-900">{{ restaurant.name }}</h1>
                    <p class="text-stone-400 text-sm mt-1">{{ restaurant.address }}</p>
                </div>
                <span :class="['px-3 py-1 rounded-full text-xs font-semibold', sc.cls]">{{ sc.label }}</span>
            </div>
            <div class="flex items-center gap-3">
                <Link
                    v-if="$can('restaurants.update')"
                    :href="`/admin/restaurants/${restaurant.id}/edit`"
                    class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-medium transition-colors"
                >
                    <Edit class="w-4 h-4" />
                    تعديل
                </Link>
                <button
                    v-if="restaurant.status === 'ACTIVE' && $can('restaurants.update')"
                    type="button"
                    :disabled="suspending"
                    class="flex items-center gap-2 px-4 py-2 bg-red-600/20 hover:bg-red-600/30 text-red-400 border border-red-500/30 rounded-lg font-medium transition-colors disabled:opacity-50"
                    @click="handleSuspend"
                >
                    <Ban class="w-4 h-4" />
                    تعليق
                </button>
                <button
                    v-else-if="$can('restaurants.update')"
                    type="button"
                    class="flex items-center gap-2 px-4 py-2 bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-500/30 rounded-lg font-medium transition-colors"
                    @click="handleActivate"
                >
                    <CheckCircle class="w-4 h-4" />
                    تفعيل
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div
                v-for="(item, i) in statCards"
                :key="i"
                class="bg-white border border-stone-200 rounded-xl p-4"
            >
                <component :is="item.icon" :class="['w-5 h-5 mb-2', item.color]" />
                <p class="text-2xl font-bold text-stone-900">{{ item.value }}</p>
                <p class="text-xs text-stone-400 mt-1">{{ item.label }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                    <h2 class="text-lg font-semibold text-stone-900 mb-4">معلومات المطعم</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div v-for="(row, idx) in infoRows" :key="idx" class="flex items-start gap-3">
                            <component :is="row.icon" class="w-4 h-4 text-stone-400 mt-0.5 shrink-0" />
                            <div>
                                <p class="text-xs text-stone-500">{{ row.label }}</p>
                                <p class="text-sm text-stone-200">{{ row.value }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="restaurant.description" class="mt-4 pt-4 border-t border-stone-200">
                        <p class="text-sm text-stone-400 leading-relaxed">{{ restaurant.description }}</p>
                    </div>
                </div>

                <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                    <h2 class="text-lg font-semibold text-stone-900 mb-4 flex items-center gap-2">
                        <DollarSign class="w-5 h-5 text-amber-400" />
                        الإعدادات المالية
                    </h2>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-white rounded-xl p-4 text-center">
                            <p class="text-2xl font-bold text-amber-400">{{ restaurant.commission_rate }}%</p>
                            <p class="text-xs text-stone-400 mt-1">نسبة العمولة</p>
                        </div>
                        <div class="bg-white rounded-xl p-4 text-center">
                            <p class="text-2xl font-bold text-indigo-400">{{ restaurant.tax_rate }}%</p>
                            <p class="text-xs text-stone-400 mt-1">نسبة الضريبة</p>
                        </div>
                        <div class="bg-white rounded-xl p-4 text-center">
                            <p class="text-sm font-bold text-emerald-400">
                                {{ restaurant.payment_method === 'BANK_TRANSFER' ? 'تحويل بنكي' : 'نقدي' }}
                            </p>
                            <p class="text-xs text-stone-400 mt-1">طريقة الدفع</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                    <h2 class="text-lg font-semibold text-stone-900 mb-4">آخر الطلبات</h2>
                    <p v-if="recentOrders.length === 0" class="text-stone-400 text-sm text-center py-8">لا توجد طلبات بعد</p>
                    <div v-else class="space-y-3">
                        <Link
                            v-for="order in recentOrders"
                            :key="order.id"
                            :href="`/admin/orders/${order.id}`"
                            class="flex items-center justify-between p-3 bg-white rounded-lg hover:bg-stone-100 transition-colors"
                        >
                            <span class="text-sm text-stone-300">#{{ order.order_number }}</span>
                            <span class="text-sm text-stone-900 font-medium">{{ (order.total_amount / 100).toFixed(2) }} ج</span>
                            <span class="text-xs text-stone-400">{{ formatDate(order.created_at) }}</span>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div v-if="restaurant.logo" class="bg-white border border-stone-200 rounded-2xl p-6 text-center shadow-xs">
                    <img :src="restaurant.logo" :alt="restaurant.name" class="w-24 h-24 rounded-full object-cover mx-auto" />
                </div>

                <div v-if="restaurant.owner" class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                    <h3 class="text-sm font-semibold text-stone-300 mb-3">مالك المطعم</h3>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-600/30 flex items-center justify-center">
                            <Users class="w-5 h-5 text-indigo-400" />
                        </div>
                        <div>
                            <p class="text-stone-900 font-medium text-sm">{{ restaurant.owner.name }}</p>
                            <p class="text-stone-400 text-xs">{{ restaurant.owner.email }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                    <h3 class="text-sm font-semibold text-stone-300 mb-3">إجراءات سريعة</h3>
                    <div class="space-y-2">
                        <Link
                            v-if="$can('restaurants.update')"
                            :href="`/admin/restaurants/${restaurant.id}/edit`"
                            class="flex items-center gap-2 w-full p-2 rounded-lg hover:bg-stone-100 text-stone-300 text-sm transition-colors"
                        >
                            <Edit class="w-4 h-4" /> تعديل بيانات المطعم
                        </Link>
                        <Link
                            v-if="$can('finance.view')"
                            :href="`/admin/finance/overview?restaurant=${restaurant.id}`"
                            class="flex items-center gap-2 w-full p-2 rounded-lg hover:bg-stone-100 text-stone-300 text-sm transition-colors"
                        >
                            <DollarSign class="w-4 h-4" /> عرض المالية
                        </Link>
                        <Link
                            :href="`/admin/invoices?restaurant=${restaurant.id}`"
                            class="flex items-center gap-2 w-full p-2 rounded-lg hover:bg-stone-100 text-stone-300 text-sm transition-colors"
                        >
                            <ShoppingBag class="w-4 h-4" /> الفواتير
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ConfirmModal
        :is-open="confirmSuspend"
        title="تعليق المطعم"
        message="هل تريد تعليق هذا المطعم؟ سيتوقف عن استقبال الطلبات تلقائياً."
        confirm-text="تعليق المطعم"
        variant="warning"
        @confirm="doSuspend"
        @cancel="confirmSuspend = false"
    />
</template>
