<script setup lang="ts">
import { ref, computed, type Component } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
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
    KeyRound,
    Save,
    Bike,
} from '@lucide/vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import { resolveMediaUrl } from '../../../lib/media';
import { subscriptionPlanLabel } from '../../../lib/subscriptionPlans';

interface Restaurant {
    id: number;
    name: string;
    slug: string;
    description: string;
    logo: string | null;
    cover_image: string | null;
    address: string;
    phone: string;
    whatsapp?: string | null;
    email: string | null;
    status: string;
    availability_label?: string;
    opening_time: string;
    closing_time: string;
    delivery_provider?: string | null;
    delivery_enabled?: boolean;
    delivery_fee: number;
    delivery_base_fee?: number;
    delivery_fee_per_km?: number;
    minimum_order_amount: number;
    estimated_delivery_time: number;
    student_discount_percentage?: number;
    commission_percentage: number;
    commission_type: string;
    monthly_subscription_fee: number;
    billing_cycle?: string | null;
    grace_period_days?: number | null;
    subscription_starts_at?: string | null;
    subscription_ends_at?: string | null;
    payment_due_date?: string | null;
    suspension_reason?: string | null;
    created_at: string;
    owner?: { id: number; name: string; email: string };
    staff_count?: number;
    orders_count?: number;
    menu_items_count?: number;
}

interface Account {
    id: number;
    name: string;
    email: string;
    role: string;
    is_active: boolean;
}

const props = defineProps<{
    restaurant: Restaurant;
    accounts?: Account[];
    stats?: {
        total_orders: number;
        completed_orders: number;
        total_revenue: number;
        platform_commission: number;
        avg_order_value: number;
        active_menu_items: number;
    };
    recentOrders?: Array<{
        id: number;
        order_number: string;
        total_amount: number;
        created_at: string;
    }>;
    billing?: {
        access_expired: boolean;
        suspended_for_billing: boolean;
    };
    invoices?: Array<{
        id: number;
        invoice_number: string;
        invoice_type: string;
        status: string;
        total_amount: number;
        paid_amount: number;
        due_date: string | null;
        issue_date: string | null;
    }>;
}>();

const stats = computed(() => props.stats ?? {
    total_orders: 0,
    completed_orders: 0,
    total_revenue: 0,
    platform_commission: 0,
    avg_order_value: 0,
    active_menu_items: 0,
});

const recentOrders = computed(() => props.recentOrders ?? []);

const accountRoleLabel = (role: string): string => {
    if (role === 'OWNER' || role === 'RESTAURANT_OWNER') {
        return 'مالك المطعم';
    }

    return 'موظف المطعم';
};

const accountForms = (props.accounts ?? []).map((account) => ({
    account,
    form: useForm({
        name: account.name,
        email: account.email,
        password: '',
    }),
}));

const money = (value: number | string | null | undefined): string => `${Number(value || 0).toFixed(2)} ج.م`;

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
    { label: 'إجمالي الطلبات', value: stats.value.total_orders, icon: ShoppingBag, color: 'text-indigo-500' },
    { label: 'الطلبات المكتملة', value: stats.value.completed_orders, icon: CheckCircle, color: 'text-emerald-500' },
    { label: 'إجمالي الإيرادات', value: money(stats.value.total_revenue), icon: DollarSign, color: 'text-amber-500' },
    { label: 'عمولة المنصة', value: money(stats.value.platform_commission), icon: Percent, color: 'text-orange-500' },
    { label: 'متوسط الطلب', value: money(stats.value.avg_order_value), icon: TrendingUp, color: 'text-purple-500' },
    { label: 'عناصر القائمة', value: stats.value.active_menu_items, icon: Star, color: 'text-pink-500' },
]);

const infoRows = computed(() => [
    { icon: Phone, label: 'الهاتف', value: props.restaurant.phone },
    { icon: Phone, label: 'واتساب', value: props.restaurant.whatsapp || '—' },
    { icon: Mail, label: 'البريد الإلكتروني', value: props.restaurant.email ?? '—' },
    { icon: MapPin, label: 'العنوان', value: props.restaurant.address },
    { icon: Clock, label: 'أوقات العمل', value: `${props.restaurant.opening_time} — ${props.restaurant.closing_time}` },
    { icon: CheckCircle, label: 'حالة الاستقبال', value: props.restaurant.availability_label ?? '—' },
    { icon: Percent, label: 'خصم الطلاب', value: `${Number(props.restaurant.student_discount_percentage || 0).toFixed(0)}%` },
    { icon: Calendar, label: 'تاريخ الانضمام', value: new Date(props.restaurant.created_at).toLocaleDateString('ar-EG') },
]);

const formatDate = (value: string): string => new Date(value).toLocaleDateString('ar-EG');

const formatDay = (value?: string | null): string => {
    if (!value) {
        return '—';
    }

    return new Date(value.slice(0, 10)).toLocaleDateString('ar-EG');
};

const commissionTypeLabels: Record<string, string> = {
    PERCENTAGE: 'نسبة من كل طلب',
    FIXED: 'مبلغ ثابت',
    SUBSCRIPTION: 'اشتراك',
    HYBRID: 'اشتراك ونسبة',
    NONE: 'بدون عمولة',
};

const deliveryProviderLabels: Record<string, string> = {
    PLATFORM: 'توصيل من الموقع',
    RESTAURANT: 'توصيل من المطعم',
    PICKUP: 'استلام من المطعم',
};

const invoiceTypeLabels: Record<string, string> = {
    SUBSCRIPTION: 'اشتراك',
    COMMISSION: 'عمولة',
    MANUAL: 'يدوي',
    COMBINED: 'مجمعة',
};

const invoiceStatusLabels: Record<string, string> = {
    DRAFT: 'مسودة',
    ISSUED: 'بانتظار السداد',
    PAID: 'مدفوعة',
    PARTIALLY_PAID: 'مدفوعة جزئياً',
    OVERDUE: 'متأخرة',
    CANCELLED: 'ملغاة',
};

const billingStatus = computed(() => {
    if (props.billing?.suspended_for_billing) {
        return { label: 'موقوف لعدم السداد', cls: 'bg-red-50 text-red-700' };
    }

    if (props.billing?.access_expired) {
        return { label: 'الاشتراك منتهي', cls: 'bg-red-50 text-red-700' };
    }

    const usesSubscription = props.restaurant.commission_type === 'SUBSCRIPTION'
        || props.restaurant.commission_type === 'HYBRID'
        || Number(props.restaurant.monthly_subscription_fee) > 0;

    if (usesSubscription && props.restaurant.subscription_ends_at) {
        return { label: 'الاشتراك ساري', cls: 'bg-emerald-50 text-emerald-700' };
    }

    if (usesSubscription) {
        return { label: 'بدون تاريخ انتهاء', cls: 'bg-amber-50 text-amber-800' };
    }

    return { label: 'محاسبة بالنسبة', cls: 'bg-amber-50 text-amber-800' };
});

const billingRows = computed(() => [
    { label: 'نوع الاشتراك', value: commissionTypeLabels[props.restaurant.commission_type] ?? props.restaurant.commission_type },
    { label: 'مدة الاشتراك', value: subscriptionPlanLabel(props.restaurant.billing_cycle) },
    { label: 'قيمة الاشتراك', value: money(props.restaurant.monthly_subscription_fee) },
    { label: 'نسبة العمولة', value: `${Number(props.restaurant.commission_percentage || 0).toFixed(0)}%` },
    { label: 'بداية الاشتراك', value: formatDay(props.restaurant.subscription_starts_at) },
    { label: 'نهاية الاشتراك', value: formatDay(props.restaurant.subscription_ends_at) },
    { label: 'تاريخ الاستحقاق', value: formatDay(props.restaurant.payment_due_date) },
    { label: 'مدة السماح', value: props.restaurant.grace_period_days == null ? '—' : `${props.restaurant.grace_period_days} يوم` },
]);

const deliveryRows = computed(() => {
    const provider = props.restaurant.delivery_provider ?? 'RESTAURANT';
    const rows = [
        { label: 'طريقة التوصيل', value: deliveryProviderLabels[provider] ?? provider },
        { label: 'وقت التوصيل', value: `${props.restaurant.estimated_delivery_time} دقيقة` },
        { label: 'الحد الأدنى للطلب', value: money(props.restaurant.minimum_order_amount) },
    ];

    if (provider === 'PICKUP') {
        return rows;
    }

    return [
        ...rows,
        { label: 'رسوم التوصيل', value: money(props.restaurant.delivery_fee) },
        { label: 'سعر فتح العداد', value: money(props.restaurant.delivery_base_fee) },
        { label: 'سعر الكيلو', value: money(props.restaurant.delivery_fee_per_km) },
        { label: 'التوصيل', value: props.restaurant.delivery_enabled === false ? 'متوقف' : 'متاح' },
    ];
});

const invoices = computed(() => props.invoices ?? []);
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
                                <p class="text-sm text-stone-900">{{ row.value }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="restaurant.description" class="mt-4 pt-4 border-t border-stone-200">
                        <p class="text-sm text-stone-400 leading-relaxed">{{ restaurant.description }}</p>
                    </div>
                </div>

                <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                    <h2 class="text-lg font-semibold text-stone-900 mb-4 flex items-center gap-2">
                        <KeyRound class="w-5 h-5 text-orange-500" />
                        حسابات الدخول
                    </h2>
                    <p v-if="accountForms.length === 0" class="text-stone-400 text-sm text-center py-8">
                        لا يوجد حساب دخول مرتبط بهذا المطعم
                    </p>
                    <div v-else class="space-y-4">
                        <form
                            v-for="entry in accountForms"
                            :key="entry.account.id"
                            class="rounded-xl border border-stone-200 p-4 space-y-4"
                            @submit.prevent="entry.form.put(`/admin/restaurants/${restaurant.id}/accounts/${entry.account.id}`, { preserveScroll: true })"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-stone-900">{{ accountRoleLabel(entry.account.role) }}</p>
                                <span
                                    :class="entry.account.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-500'"
                                    class="px-2.5 py-1 rounded-full text-xs font-medium"
                                >
                                    {{ entry.account.is_active ? 'نشط' : 'موقوف' }}
                                </span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm text-stone-500 mb-1">الاسم</label>
                                    <input
                                        v-model="entry.form.name"
                                        type="text"
                                        :disabled="!$can('restaurants.update')"
                                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 disabled:bg-stone-50"
                                    />
                                    <p v-if="entry.form.errors.name" class="text-red-500 text-xs mt-1">{{ entry.form.errors.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm text-stone-500 mb-1">البريد الإلكتروني (اسم الدخول)</label>
                                    <input
                                        v-model="entry.form.email"
                                        type="email"
                                        dir="ltr"
                                        :disabled="!$can('restaurants.update')"
                                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 disabled:bg-stone-50"
                                    />
                                    <p v-if="entry.form.errors.email" class="text-red-500 text-xs mt-1">{{ entry.form.errors.email }}</p>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm text-stone-500 mb-1">كلمة المرور الجديدة</label>
                                    <input
                                        v-model="entry.form.password"
                                        type="password"
                                        placeholder="اتركها فارغة إن لم ترد تغييرها"
                                        :disabled="!$can('restaurants.update')"
                                        class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 disabled:bg-stone-50"
                                    />
                                    <p v-if="entry.form.errors.password" class="text-red-500 text-xs mt-1">{{ entry.form.errors.password }}</p>
                                </div>
                            </div>
                            <div v-if="$can('restaurants.update')" class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="entry.form.processing"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg text-sm font-medium disabled:opacity-50"
                                >
                                    <Save class="w-4 h-4" />
                                    حفظ الحساب
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-stone-900 flex items-center gap-2">
                            <DollarSign class="w-5 h-5 text-amber-400" />
                            الاشتراك والمحاسبة
                        </h2>
                        <span :class="['px-2.5 py-1 rounded-full text-xs font-medium', billingStatus.cls]">
                            {{ billingStatus.label }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                        <div v-for="row in billingRows" :key="row.label" class="rounded-xl bg-stone-50 p-3">
                            <p class="text-xs text-stone-500">{{ row.label }}</p>
                            <p class="mt-1 text-sm font-semibold text-stone-900">{{ row.value }}</p>
                        </div>
                    </div>
                    <p v-if="restaurant.suspension_reason" class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        سبب الإيقاف: {{ restaurant.suspension_reason }}
                    </p>
                </div>

                <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                    <h2 class="text-lg font-semibold text-stone-900 mb-4 flex items-center gap-2">
                        <Bike class="w-5 h-5 text-sky-500" />
                        التوصيل والاستلام
                    </h2>
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                        <div v-for="row in deliveryRows" :key="row.label">
                            <p class="text-xs text-stone-500">{{ row.label }}</p>
                            <p class="mt-1 text-sm font-semibold text-stone-900">{{ row.value }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                    <h2 class="text-lg font-semibold text-stone-900 mb-4">آخر الفواتير</h2>
                    <p v-if="invoices.length === 0" class="text-stone-400 text-sm text-center py-8">لا توجد فواتير بعد</p>
                    <div v-else class="space-y-3">
                        <Link
                            v-for="invoice in invoices"
                            :key="invoice.id"
                            :href="`/admin/invoices/${invoice.id}`"
                            class="flex items-center justify-between gap-3 rounded-lg p-3 hover:bg-stone-100"
                        >
                            <span class="text-sm text-stone-900">#{{ invoice.invoice_number }}</span>
                            <span class="text-xs text-stone-500">{{ invoiceTypeLabels[invoice.invoice_type] ?? invoice.invoice_type }}</span>
                            <span class="text-xs text-stone-500">{{ invoiceStatusLabels[invoice.status] ?? invoice.status }}</span>
                            <span class="text-sm font-medium text-stone-900">{{ money(invoice.total_amount) }}</span>
                            <span class="text-xs text-stone-400">{{ formatDay(invoice.due_date) }}</span>
                        </Link>
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
                            <span class="text-sm text-stone-900 font-medium">{{ money(order.total_amount) }}</span>
                            <span class="text-xs text-stone-400">{{ formatDate(order.created_at) }}</span>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div v-if="restaurant.logo" class="bg-white border border-stone-200 rounded-2xl p-6 text-center shadow-xs">
                    <img :src="resolveMediaUrl(restaurant.logo, '')" :alt="restaurant.name" class="w-24 h-24 rounded-full object-cover mx-auto" />
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
                    <h3 class="text-sm font-semibold text-stone-900 mb-3">إجراءات سريعة</h3>
                    <div class="space-y-2">
                        <Link
                            v-if="$can('restaurants.update')"
                            :href="`/admin/restaurants/${restaurant.id}/edit`"
                            class="flex items-center gap-2 w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm font-medium text-stone-800 transition-colors hover:bg-stone-50"
                        >
                            <Edit class="w-4 h-4 text-indigo-500" /> تعديل بيانات المطعم
                        </Link>
                        <Link
                            v-if="$can('finance.view')"
                            :href="`/admin/finance/restaurants/${restaurant.id}`"
                            class="flex items-center gap-2 w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm font-medium text-stone-800 transition-colors hover:bg-stone-50"
                        >
                            <DollarSign class="w-4 h-4 text-amber-500" /> عرض المالية
                        </Link>
                        <Link
                            v-if="$can('billing.view')"
                            :href="`/admin/billing?restaurant_id=${restaurant.id}`"
                            class="flex items-center gap-2 w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm font-medium text-stone-800 transition-colors hover:bg-stone-50"
                        >
                            <ShoppingBag class="w-4 h-4 text-emerald-500" /> فواتير المطعم
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
