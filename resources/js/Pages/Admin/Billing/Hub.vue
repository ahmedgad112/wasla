<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Zap,
    Lock,
    CheckCircle2,
    Clock,
    AlertTriangle,
    Plus,
    Receipt,
    CreditCard,
    TrendingUp,
    Store,
    XCircle,
    ChevronLeft,
    ChevronRight,
    Banknote,
    RefreshCw,
    ShieldOff,
    Pencil,
} from '@lucide/vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';

interface Restaurant {
    id: number;
    name: string;
    status: string;
    billing_suspended_at?: string | null;
    commission_type?: string;
    commission_percentage?: number;
    monthly_subscription_fee?: number;
    overdue_invoices_count?: number;
}

interface Invoice {
    id: number;
    invoice_number: string;
    restaurant: { id: number; name: string; status: string };
    total_amount: string;
    paid_amount: string;
    status: string;
    invoice_type: string;
    issue_date: string;
    due_date: string;
    notes?: string;
}

interface OpenInvoice {
    id: number;
    invoice_number: string;
    restaurant_id: number;
    total_amount: number;
    paid_amount: number;
    remaining: number;
    status: string;
    due_date: string;
    invoice_type: string;
}

interface Collection {
    id: number;
    amount: number;
    collection_date: string;
    payment_method: string;
    notes?: string;
    restaurant: { id: number; name: string };
    collectedByUser?: { name: string } | null;
}

interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
}

interface Stats {
    total_revenue: number;
    total_pending: number;
    overdue_count: number;
    total_collected: number;
}

const props = defineProps<{
    stats: Stats;
    invoices: Paginated<Invoice>;
    collections: Paginated<Collection>;
    openInvoices: OpenInvoice[];
    overdueRestaurants: Restaurant[];
    restaurants: Restaurant[];
    filters: { inv_status?: string; restaurant_id?: string };
}>();

const invoiceStatusMap: Record<string, { label: string; cls: string }> = {
    DRAFT: { label: 'مسودة', cls: 'bg-stone-100 text-stone-600 dark:bg-stone-800 dark:text-stone-300' },
    ISSUED: { label: 'بانتظار السداد', cls: 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300' },
    PAID: { label: 'مدفوعة', cls: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' },
    PARTIALLY_PAID: { label: 'مدفوعة جزئياً', cls: 'bg-teal-100 text-teal-800 dark:bg-teal-950/60 dark:text-teal-300' },
    OVERDUE: { label: 'متأخرة', cls: 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300' },
    CANCELLED: { label: 'ملغاة', cls: 'bg-stone-100 text-stone-500 dark:bg-stone-800 dark:text-stone-400' },
};

const invoiceTypeMap: Record<string, string> = {
    COMMISSION: 'عمولة',
    SUBSCRIPTION: 'اشتراك',
    MANUAL: 'يدوي',
    COMBINED: 'مجمّع',
};

const paymentMethodMap: Record<string, string> = {
    CASH: 'نقدي',
    BANK_TRANSFER: 'تحويل بنكي',
    VODAFONE_CASH: 'فودافون كاش',
    INSTAPAY: 'انستاباي',
};

const invoiceStatusFilters = ['', 'ISSUED', 'PAID', 'OVERDUE', 'PARTIALLY_PAID', 'CANCELLED'];

const fieldClass =
    'w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2.5 text-sm text-stone-900 focus:border-orange-500 focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-white';

const fmt = (v: number | string): string => {
    const num = Number(v);
    if (isNaN(num)) {
        return '0';
    }
    return Number(num.toFixed(2)).toLocaleString('ar-EG');
};

const formatDate = (value: string): string => new Date(value).toLocaleDateString('ar-EG');

const todayKey = (): string => {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${now.getFullYear()}-${month}-${day}`;
};

const isSettled = (status: string): boolean => status === 'PAID' || status === 'CANCELLED';

const isPastDue = (inv: { status: string; due_date: string }): boolean =>
    !isSettled(inv.status) && inv.due_date.slice(0, 10) <= todayKey();

const invoiceRowStatus = (inv: Invoice): { label: string; cls: string } => {
    if (inv.status === 'PARTIALLY_PAID') {
        return invoiceStatusMap.PARTIALLY_PAID;
    }
    if (isPastDue(inv)) {
        return invoiceStatusMap.OVERDUE;
    }
    return invoiceStatusMap[inv.status] ?? invoiceStatusMap.ISSUED;
};

const remainingOf = (inv: Invoice): number => Math.max(Number(inv.total_amount) - Number(inv.paid_amount), 0);

type TabId = 'overview' | 'invoices' | 'collections' | 'overdue';

const activeTab = ref<TabId>('overview');
const confirm = ref<{ msg: string; action: () => void; danger?: boolean } | null>(null);
const showCollectionForm = ref(false);
const showInvoiceForm = ref(false);
const editingInvoice = ref<Invoice | null>(null);

const collectionForm = useForm({
    restaurant_id: '',
    invoice_id: '',
    amount: '',
    payment_method: 'CASH',
    collection_date: new Date().toISOString().split('T')[0],
    notes: '',
});

const invoiceForm = useForm({
    restaurant_id: props.restaurants[0]?.id ? String(props.restaurants[0].id) : '',
    invoice_type: 'SUBSCRIPTION',
    subtotal: '',
    due_date: new Date(Date.now() + 7 * 86400000).toISOString().split('T')[0],
    notes: '',
});

const editForm = useForm({
    subtotal: '',
    due_date: '',
    invoice_type: 'SUBSCRIPTION',
    notes: '',
});

const tabs = computed(() => [
    { id: 'overview' as const, label: 'لوحة التحكم', icon: RefreshCw },
    { id: 'invoices' as const, label: 'الفواتير', icon: Receipt },
    { id: 'collections' as const, label: 'سجل التحصيل', icon: CreditCard },
    {
        id: 'overdue' as const,
        label: `المتأخرون (${props.overdueRestaurants.length})`,
        icon: AlertTriangle,
    },
]);

const openInvoicesForRestaurant = computed(() =>
    props.openInvoices.filter((invoice) => String(invoice.restaurant_id) === String(collectionForm.restaurant_id)),
);

watch(
    () => collectionForm.restaurant_id,
    () => {
        const matches = openInvoicesForRestaurant.value;
        const currentStillValid = matches.some((invoice) => String(invoice.id) === String(collectionForm.invoice_id));
        if (currentStillValid) {
            return;
        }
        collectionForm.invoice_id = matches.length === 1 ? String(matches[0].id) : '';
    },
);

watch(
    () => collectionForm.invoice_id,
    (invoiceId) => {
        const invoice = props.openInvoices.find((item) => String(item.id) === String(invoiceId));
        if (invoice) {
            collectionForm.amount = String(invoice.remaining);
        }
    },
);

const openEditInvoice = (inv: Invoice): void => {
    editingInvoice.value = inv;
    editForm.setData({
        subtotal: String(inv.total_amount),
        due_date: inv.due_date.slice(0, 10),
        invoice_type: inv.invoice_type || 'SUBSCRIPTION',
        notes: inv.notes || '',
    });
};

const submitEditInvoice = (): void => {
    if (!editingInvoice.value) {
        return;
    }
    editForm.put(`/admin/billing/invoice/${editingInvoice.value.id}`, {
        onSuccess: () => {
            editingInvoice.value = null;
            editForm.reset();
        },
    });
};

const ask = (msg: string, action: () => void, danger = false): void => {
    confirm.value = { msg, action, danger };
};

const handleConfirm = (): void => {
    if (confirm.value) {
        confirm.value.action();
        confirm.value = null;
    }
};

const doAutoGenerate = (): void =>
    ask('سيتم توليد فواتير هذا الشهر لجميع المطاعم تلقائياً. هل تريد المتابعة؟', () => {
        router.post('/admin/billing/auto-generate');
    });

const doAutoLock = (): void =>
    ask(
        'سيتم قفل جميع حسابات المطاعم التي لديها فواتير لم تسدّد بعد. هل تريد المتابعة؟',
        () => {
            router.post('/admin/billing/auto-lock-overdue');
        },
        true,
    );

const doMarkPaid = (id: number, num: string): void =>
    ask(`هل تريد تسجيل الفاتورة ${num} كمدفوعة بالكامل وإعادة تفعيل المطعم؟`, () => {
        router.post(`/admin/billing/invoice/${id}/mark-paid`);
    });

const doSuspend = (id: number, num: string): void =>
    ask(
        `هل تريد قفل المطعم بسبب عدم سداد الفاتورة ${num}؟`,
        () => {
            router.post(`/admin/billing/invoice/${id}/suspend`);
        },
        true,
    );

const doCancel = (id: number, num: string): void =>
    ask(
        `هل تريد إلغاء الفاتورة ${num}؟`,
        () => {
            router.post(`/admin/billing/invoice/${id}/cancel`);
        },
        true,
    );

const lockRestaurant = (r: Restaurant): void =>
    ask(`هل تريد قفل مطعم "${r.name}"؟`, () => router.post(`/admin/billing/restaurant/${r.id}/suspend`), true);

const openCollectionFor = (inv?: Invoice): void => {
    activeTab.value = 'collections';
    showCollectionForm.value = true;
    if (!inv) {
        return;
    }
    collectionForm.restaurant_id = String(inv.restaurant.id);
    collectionForm.invoice_id = String(inv.id);
    collectionForm.amount = String(remainingOf(inv));
};

const submitCollection = (): void => {
    collectionForm.post('/admin/billing/collection', {
        onSuccess: () => {
            collectionForm.reset();
            collectionForm.payment_method = 'CASH';
            collectionForm.collection_date = new Date().toISOString().split('T')[0];
            showCollectionForm.value = false;
        },
    });
};

const submitInvoice = (): void => {
    invoiceForm.post('/admin/billing/invoice', {
        onSuccess: () => {
            invoiceForm.reset();
            invoiceForm.invoice_type = 'SUBSCRIPTION';
            invoiceForm.restaurant_id = props.restaurants[0]?.id ? String(props.restaurants[0].id) : '';
            showInvoiceForm.value = false;
        },
    });
};

const filterInvoices = (status: string): void => {
    router.get(
        '/admin/billing',
        { inv_status: status || undefined, restaurant_id: props.filters.restaurant_id },
        { preserveState: true },
    );
};

const filterRestaurant = (event: Event): void => {
    const value = (event.target as HTMLSelectElement).value;
    router.get(
        '/admin/billing',
        { inv_status: props.filters.inv_status, restaurant_id: value || undefined },
        { preserveState: true },
    );
};

const goInvoicePage = (page: number): void => {
    router.get('/admin/billing', { ...props.filters, inv_page: page }, { preserveState: true });
};

const goCollectionPage = (page: number): void => {
    router.get('/admin/billing', { ...props.filters, col_page: page }, { preserveState: true });
};
</script>

<template>
    <Head title="مركز التحصيل — الإدارة المركزية" />

    <ConfirmModal
        :is-open="confirm !== null"
        :message="confirm?.msg ?? ''"
        :variant="confirm?.danger ? 'danger' : 'info'"
        @confirm="handleConfirm"
        @cancel="confirm = null"
    />

    <div class="space-y-6" dir="rtl">
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-xs dark:border-stone-800 dark:bg-stone-900">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="rounded-2xl bg-orange-600 p-3 text-white shadow-md shadow-orange-600/20">
                        <Banknote class="h-6 w-6" />
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-stone-900 dark:text-white">مركز التحصيل</h1>
                        <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">
                            إصدار الفواتير، تسجيل السداد، وقفل الحسابات المتأخرة من مكان واحد
                        </p>
                    </div>
                </div>

                <div v-if="$can('billing.manage')" class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-amber-600"
                        @click="doAutoGenerate"
                    >
                        <Zap class="h-4 w-4" />
                        توليد فواتير الشهر
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs transition hover:bg-red-700"
                        @click="doAutoLock"
                    >
                        <Lock class="h-4 w-4" />
                        قفل المتأخرين
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-3xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
                <div class="mb-2 flex items-center justify-between text-xs font-bold text-stone-400">
                    <span>فواتير مسددة</span>
                    <TrendingUp class="h-4 w-4 text-emerald-600" />
                </div>
                <p class="text-2xl font-black text-stone-900 dark:text-white">
                    {{ fmt(stats.total_revenue) }}
                    <span class="text-xs font-normal text-stone-400">ج.م</span>
                </p>
            </div>
            <div class="rounded-3xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
                <div class="mb-2 flex items-center justify-between text-xs font-bold text-stone-400">
                    <span>مستحقات معلّقة</span>
                    <Clock class="h-4 w-4 text-amber-500" />
                </div>
                <p class="text-2xl font-black text-stone-900 dark:text-white">
                    {{ fmt(stats.total_pending) }}
                    <span class="text-xs font-normal text-stone-400">ج.م</span>
                </p>
            </div>
            <div class="rounded-3xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
                <div class="mb-2 flex items-center justify-between text-xs font-bold text-stone-400">
                    <span>فواتير متأخرة</span>
                    <AlertTriangle class="h-4 w-4 text-red-500" />
                </div>
                <p class="text-2xl font-black text-stone-900 dark:text-white">{{ stats.overdue_count }}</p>
            </div>
            <div class="rounded-3xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
                <div class="mb-2 flex items-center justify-between text-xs font-bold text-stone-400">
                    <span>سندات التحصيل</span>
                    <CreditCard class="h-4 w-4 text-orange-500" />
                </div>
                <p class="text-2xl font-black text-stone-900 dark:text-white">
                    {{ fmt(stats.total_collected) }}
                    <span class="text-xs font-normal text-stone-400">ج.م</span>
                </p>
            </div>
        </div>

        <div class="flex gap-1 overflow-x-auto rounded-2xl border border-stone-200 bg-white p-1.5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                :class="[
                    'flex items-center gap-2 whitespace-nowrap rounded-xl px-4 py-2.5 text-xs font-bold transition-all',
                    activeTab === tab.id
                        ? 'bg-orange-600 text-white shadow-md shadow-orange-600/30'
                        : 'text-stone-500 hover:bg-stone-50 hover:text-stone-900 dark:text-stone-400 dark:hover:bg-stone-800 dark:hover:text-white',
                ]"
                @click="activeTab = tab.id"
            >
                <component :is="tab.icon" class="h-4 w-4 shrink-0" />
                {{ tab.label }}
            </button>
        </div>

        <div v-if="activeTab === 'overview'" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="space-y-4 rounded-3xl border border-stone-200 bg-white p-6 shadow-xs dark:border-stone-800 dark:bg-stone-900">
                <h2 class="flex items-center gap-2 font-black text-stone-900 dark:text-white">
                    <RefreshCw class="h-5 w-5 text-orange-500" />
                    العمليات التلقائية
                </h2>
                <p class="text-sm text-stone-500 dark:text-stone-400">تنفّذ على كل المطاعم دفعة واحدة</p>

                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-4 rounded-2xl border border-stone-200 bg-stone-50 p-4 dark:border-stone-800 dark:bg-stone-800/50">
                        <div>
                            <p class="text-sm font-bold text-stone-900 dark:text-white">توليد فواتير الشهر الحالي</p>
                            <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">
                                ينشئ فاتورة لكل مطعم لم تصدر له فاتورة هذا الشهر
                            </p>
                        </div>
                        <button
                            v-if="$can('billing.manage')"
                            type="button"
                            class="shrink-0 rounded-xl bg-amber-500 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-amber-600"
                            @click="doAutoGenerate"
                        >
                            تنفيذ
                        </button>
                    </div>
                    <div class="flex items-center justify-between gap-4 rounded-2xl border border-stone-200 bg-stone-50 p-4 dark:border-stone-800 dark:bg-stone-800/50">
                        <div>
                            <p class="text-sm font-bold text-stone-900 dark:text-white">قفل الحسابات المتأخرة</p>
                            <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">
                                يوقف المطاعم التي تجاوزت تاريخ الاستحقاق ولم تسدّد
                            </p>
                        </div>
                        <button
                            v-if="$can('billing.manage')"
                            type="button"
                            class="shrink-0 rounded-xl bg-red-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-red-700"
                            @click="doAutoLock"
                        >
                            قفل الآن
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-4 rounded-3xl border border-stone-200 bg-white p-6 shadow-xs dark:border-stone-800 dark:bg-stone-900">
                <h2 class="flex items-center gap-2 font-black text-stone-900 dark:text-white">
                    <CreditCard class="h-5 w-5 text-emerald-600" />
                    تسجيل تحصيل سريع
                </h2>
                <p class="text-sm text-stone-500 dark:text-stone-400">
                    السند يرتبط بفاتورة مفتوحة ويحدّث المتبقي وحالة المطعم عند اكتمال السداد.
                </p>
                <button
                    v-if="$can('billing.manage')"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-700"
                    @click="openCollectionFor()"
                >
                    <Plus class="h-4 w-4" />
                    تسجيل سند تحصيل
                </button>
            </div>

            <div
                v-if="overdueRestaurants.length > 0"
                class="col-span-full rounded-3xl border border-red-200 bg-red-50 p-5 dark:border-red-900/50 dark:bg-red-950/30"
            >
                <h3 class="mb-3 flex items-center gap-2 font-black text-red-700 dark:text-red-300">
                    <AlertTriangle class="h-5 w-5" />
                    {{ overdueRestaurants.length }} مطعم لم يسدّد مستحقاته
                </h3>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="r in overdueRestaurants"
                        :key="r.id"
                        class="rounded-full border border-red-200 bg-white px-3 py-1 text-xs font-bold text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300"
                    >
                        {{ r.name }} ({{ r.overdue_invoices_count }} فاتورة)
                    </span>
                </div>
                <button
                    v-if="$can('billing.manage')"
                    type="button"
                    class="mt-4 inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-red-700"
                    @click="doAutoLock"
                >
                    <Lock class="h-4 w-4" />
                    قفل جميعهم الآن
                </button>
            </div>
        </div>

        <div v-if="activeTab === 'invoices'" class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="s in invoiceStatusFilters"
                        :key="s || 'all'"
                        type="button"
                        :class="[
                            'rounded-xl px-3 py-1.5 text-xs font-bold transition',
                            (filters.inv_status || '') === s
                                ? 'bg-orange-600 text-white'
                                : 'bg-stone-100 text-stone-600 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300',
                        ]"
                        @click="filterInvoices(s)"
                    >
                        {{ s ? invoiceStatusMap[s]?.label : 'الكل' }}
                    </button>
                    <select
                        :value="filters.restaurant_id || ''"
                        :class="fieldClass + ' w-auto min-w-40'"
                        @change="filterRestaurant"
                    >
                        <option value="">كل المطاعم</option>
                        <option v-for="r in restaurants" :key="r.id" :value="String(r.id)">{{ r.name }}</option>
                    </select>
                </div>
                <button
                    v-if="$can('billing.manage')"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-orange-700"
                    @click="showInvoiceForm = !showInvoiceForm"
                >
                    <Plus class="h-4 w-4" />
                    فاتورة جديدة
                </button>
            </div>

            <div v-if="showInvoiceForm" class="rounded-3xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
                <h3 class="mb-4 font-black text-stone-900 dark:text-white">إصدار فاتورة مخصصة</h3>
                <form class="grid grid-cols-1 gap-3 md:grid-cols-4" @submit.prevent="submitInvoice">
                    <div>
                        <label class="mb-1 block text-xs font-bold text-stone-600 dark:text-stone-300">المطعم *</label>
                        <select v-model="invoiceForm.restaurant_id" :class="fieldClass">
                            <option v-for="r in restaurants" :key="r.id" :value="String(r.id)">{{ r.name }}</option>
                        </select>
                        <p v-if="invoiceForm.errors.restaurant_id" class="mt-1 text-xs text-red-600">
                            {{ invoiceForm.errors.restaurant_id }}
                        </p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-stone-600 dark:text-stone-300">النوع *</label>
                        <select v-model="invoiceForm.invoice_type" :class="fieldClass">
                            <option value="SUBSCRIPTION">اشتراك شهري</option>
                            <option value="COMMISSION">عمولة</option>
                            <option value="MANUAL">يدوي</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-stone-600 dark:text-stone-300">المبلغ (ج.م) *</label>
                        <input v-model="invoiceForm.subtotal" type="number" step="0.01" placeholder="0.00" :class="fieldClass" />
                        <p v-if="invoiceForm.errors.subtotal" class="mt-1 text-xs text-red-600">{{ invoiceForm.errors.subtotal }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-stone-600 dark:text-stone-300">تاريخ الاستحقاق *</label>
                        <input v-model="invoiceForm.due_date" type="date" :class="fieldClass" />
                        <p v-if="invoiceForm.errors.due_date" class="mt-1 text-xs text-red-600">{{ invoiceForm.errors.due_date }}</p>
                    </div>
                    <div class="flex justify-end gap-2 md:col-span-4">
                        <button
                            type="button"
                            class="rounded-xl border border-stone-200 px-4 py-2 text-xs font-bold text-stone-600 transition hover:bg-stone-50 dark:border-stone-700 dark:text-stone-300"
                            @click="showInvoiceForm = false"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="invoiceForm.processing"
                            class="rounded-xl bg-orange-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-orange-700 disabled:opacity-50"
                        >
                            {{ invoiceForm.processing ? 'جاري الإصدار...' : 'إصدار الفاتورة' }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-xs dark:border-stone-800 dark:bg-stone-900">
                <div class="overflow-x-auto">
                    <table class="record-cards w-full text-sm">
                        <thead class="border-b border-stone-200 dark:border-stone-800">
                            <tr class="text-right text-xs text-stone-400">
                                <th class="px-5 py-3 font-bold">رقم الفاتورة</th>
                                <th class="px-5 py-3 font-bold">المطعم</th>
                                <th class="px-5 py-3 font-bold">النوع</th>
                                <th class="px-5 py-3 font-bold">المبلغ</th>
                                <th class="px-5 py-3 font-bold">المتبقي</th>
                                <th class="px-5 py-3 font-bold">الحالة</th>
                                <th class="px-5 py-3 font-bold">الاستحقاق</th>
                                <th class="px-5 py-3 font-bold">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                            <tr
                                v-for="inv in invoices.data"
                                :key="inv.id"
                                :class="['transition-colors hover:bg-stone-50 dark:hover:bg-stone-800/50', isPastDue(inv) ? 'bg-red-50/70 dark:bg-red-950/20' : '']"
                            >
                                <td data-label="رقم الفاتورة" class="is-title px-5 py-3 font-mono text-xs font-bold text-orange-600">
                                    {{ inv.invoice_number }}
                                </td>
                                <td data-label="المطعم" class="px-5 py-3 font-bold text-stone-900 dark:text-white">{{ inv.restaurant.name }}</td>
                                <td data-label="النوع" class="px-5 py-3 text-xs text-stone-500">
                                    {{ invoiceTypeMap[inv.invoice_type] ?? inv.invoice_type }}
                                </td>
                                <td data-label="المبلغ" class="px-5 py-3 font-black text-stone-900 dark:text-white">{{ fmt(inv.total_amount) }} ج.م</td>
                                <td data-label="المتبقي" class="px-5 py-3 font-bold text-amber-700 dark:text-amber-300">
                                    {{ fmt(remainingOf(inv)) }} ج.م
                                </td>
                                <td data-label="الحالة" class="px-5 py-3">
                                    <span :class="['rounded-full px-2.5 py-1 text-[10px] font-bold', invoiceRowStatus(inv).cls]">
                                        {{ invoiceRowStatus(inv).label }}
                                    </span>
                                </td>
                                <td
                                    data-label="الاستحقاق"
                                    :class="['px-5 py-3 text-xs', isPastDue(inv) ? 'font-bold text-red-600' : 'text-stone-500']"
                                >
                                    {{ formatDate(inv.due_date) }}
                                </td>
                                <td data-label="إجراءات" class="is-actions px-5 py-3">
                                    <div v-if="$can('billing.manage') && !isSettled(inv.status)" class="flex flex-wrap items-center gap-1.5">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-xl bg-emerald-600 px-2.5 py-1.5 text-[11px] font-bold text-white transition hover:bg-emerald-700"
                                            @click="openCollectionFor(inv)"
                                        >
                                            <Banknote class="h-3.5 w-3.5" />
                                            تحصيل
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-xl bg-blue-600 px-2.5 py-1.5 text-[11px] font-bold text-white transition hover:bg-blue-700"
                                            @click="openEditInvoice(inv)"
                                        >
                                            <Pencil class="h-3.5 w-3.5" />
                                            تعديل
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-xl bg-emerald-100 px-2.5 py-1.5 text-[11px] font-bold text-emerald-800 transition hover:bg-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300"
                                            @click="doMarkPaid(inv.id, inv.invoice_number)"
                                        >
                                            <CheckCircle2 class="inline h-3.5 w-3.5" />
                                            سداد كامل
                                        </button>
                                        <button
                                            v-if="inv.restaurant.status !== 'SUSPENDED'"
                                            type="button"
                                            class="rounded-xl bg-red-100 px-2.5 py-1.5 text-[11px] font-bold text-red-700 transition hover:bg-red-200 dark:bg-red-950/50 dark:text-red-300"
                                            @click="doSuspend(inv.id, inv.invoice_number)"
                                        >
                                            <ShieldOff class="inline h-3.5 w-3.5" />
                                            قفل
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-xl border border-stone-200 px-2.5 py-1.5 text-[11px] font-bold text-stone-500 transition hover:text-red-600 dark:border-stone-700"
                                            @click="doCancel(inv.id, inv.invoice_number)"
                                        >
                                            <XCircle class="inline h-3.5 w-3.5" />
                                            إلغاء
                                        </button>
                                    </div>
                                    <span v-else-if="inv.status === 'PAID'" class="text-xs font-bold text-emerald-600">مسددة</span>
                                    <span v-else class="text-xs text-stone-400">ملغاة</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="invoices.data.length === 0" class="py-12 text-center text-sm text-stone-500">لا توجد فواتير</p>
                </div>
                <div v-if="invoices.last_page > 1" class="flex items-center justify-between border-t border-stone-200 px-5 py-3 dark:border-stone-800">
                    <p class="text-xs text-stone-500">إجمالي {{ invoices.total }} سجل</p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            :disabled="invoices.current_page === 1"
                            class="rounded-lg border border-stone-200 p-1.5 text-stone-500 transition hover:bg-stone-50 disabled:opacity-30 dark:border-stone-700"
                            @click="goInvoicePage(invoices.current_page - 1)"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </button>
                        <span class="text-xs text-stone-500">{{ invoices.current_page }} / {{ invoices.last_page }}</span>
                        <button
                            type="button"
                            :disabled="invoices.current_page === invoices.last_page"
                            class="rounded-lg border border-stone-200 p-1.5 text-stone-500 transition hover:bg-stone-50 disabled:opacity-30 dark:border-stone-700"
                            @click="goInvoicePage(invoices.current_page + 1)"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="activeTab === 'collections'" class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-black text-stone-900 dark:text-white">سجل التحصيلات</h2>
                <button
                    v-if="$can('billing.manage')"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-emerald-700"
                    @click="showCollectionForm = !showCollectionForm"
                >
                    <Plus class="h-4 w-4" />
                    تسجيل تحصيل
                </button>
            </div>

            <div v-if="showCollectionForm" class="rounded-3xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900">
                <h3 class="mb-4 font-black text-stone-900 dark:text-white">سند تحصيل جديد</h3>
                <form class="grid grid-cols-1 gap-3 md:grid-cols-3" @submit.prevent="submitCollection">
                    <div>
                        <label class="mb-1 block text-xs font-bold text-stone-600 dark:text-stone-300">المطعم *</label>
                        <select v-model="collectionForm.restaurant_id" required :class="fieldClass">
                            <option value="">اختر المطعم</option>
                            <option v-for="r in restaurants" :key="r.id" :value="String(r.id)">{{ r.name }}</option>
                        </select>
                        <p v-if="collectionForm.errors.restaurant_id" class="mt-1 text-xs text-red-600">
                            {{ collectionForm.errors.restaurant_id }}
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-bold text-stone-600 dark:text-stone-300">الفاتورة المفتوحة</label>
                        <select
                            v-model="collectionForm.invoice_id"
                            :required="openInvoicesForRestaurant.length > 0"
                            :disabled="!collectionForm.restaurant_id"
                            :class="fieldClass"
                        >
                            <option value="">
                                {{
                                    !collectionForm.restaurant_id
                                        ? 'اختر المطعم أولاً'
                                        : openInvoicesForRestaurant.length > 0
                                          ? 'اختر الفاتورة'
                                          : 'بدون فاتورة مفتوحة'
                                }}
                            </option>
                            <option v-for="invoice in openInvoicesForRestaurant" :key="invoice.id" :value="String(invoice.id)">
                                {{ invoice.invoice_number }} — متبقي {{ fmt(invoice.remaining) }} ج.م —
                                {{ invoiceTypeMap[invoice.invoice_type] ?? invoice.invoice_type }}
                            </option>
                        </select>
                        <p v-if="collectionForm.errors.invoice_id" class="mt-1 text-xs text-red-600">
                            {{ collectionForm.errors.invoice_id }}
                        </p>
                        <p v-else-if="collectionForm.restaurant_id && openInvoicesForRestaurant.length === 0" class="mt-1 text-xs text-stone-500">
                            لا توجد فاتورة مفتوحة لهذا المطعم. السند سيُسجَّل بدون تحديث فاتورة.
                        </p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-stone-600 dark:text-stone-300">المبلغ (ج.م) *</label>
                        <input v-model="collectionForm.amount" type="number" step="0.01" required placeholder="0.00" :class="fieldClass" />
                        <p v-if="collectionForm.errors.amount" class="mt-1 text-xs text-red-600">{{ collectionForm.errors.amount }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-stone-600 dark:text-stone-300">طريقة الدفع *</label>
                        <select v-model="collectionForm.payment_method" :class="fieldClass">
                            <option v-for="(label, value) in paymentMethodMap" :key="value" :value="value">{{ label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-stone-600 dark:text-stone-300">تاريخ التحصيل</label>
                        <input v-model="collectionForm.collection_date" type="date" :class="fieldClass" />
                    </div>
                    <div class="md:col-span-3">
                        <label class="mb-1 block text-xs font-bold text-stone-600 dark:text-stone-300">ملاحظات</label>
                        <input v-model="collectionForm.notes" type="text" placeholder="اختياري" :class="fieldClass" />
                    </div>
                    <div class="flex justify-end gap-2 md:col-span-3">
                        <button
                            type="button"
                            class="rounded-xl border border-stone-200 px-4 py-2 text-xs font-bold text-stone-600 transition hover:bg-stone-50 dark:border-stone-700 dark:text-stone-300"
                            @click="showCollectionForm = false"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="collectionForm.processing"
                            class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-emerald-700 disabled:opacity-50"
                        >
                            {{ collectionForm.processing ? 'جاري الحفظ...' : 'حفظ التحصيل' }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-xs dark:border-stone-800 dark:bg-stone-900">
                <div class="overflow-x-auto">
                    <table class="record-cards w-full text-sm">
                        <thead class="border-b border-stone-200 dark:border-stone-800">
                            <tr class="text-right text-xs text-stone-400">
                                <th class="px-5 py-3 font-bold">المطعم</th>
                                <th class="px-5 py-3 font-bold">المبلغ</th>
                                <th class="px-5 py-3 font-bold">طريقة الدفع</th>
                                <th class="px-5 py-3 font-bold">تاريخ التحصيل</th>
                                <th class="px-5 py-3 font-bold">بواسطة</th>
                                <th class="px-5 py-3 font-bold">ملاحظات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                            <tr v-for="col in collections.data" :key="col.id" class="transition-colors hover:bg-stone-50 dark:hover:bg-stone-800/50">
                                <td data-label="المطعم" class="is-title px-5 py-3 font-bold text-stone-900 dark:text-white">
                                    {{ col.restaurant.name }}
                                </td>
                                <td data-label="المبلغ" class="px-5 py-3 font-black text-emerald-700 dark:text-emerald-300">
                                    {{ fmt(col.amount) }} ج.م
                                </td>
                                <td data-label="طريقة الدفع" class="px-5 py-3 text-xs text-stone-600 dark:text-stone-300">
                                    {{ paymentMethodMap[col.payment_method] ?? col.payment_method }}
                                </td>
                                <td data-label="تاريخ التحصيل" class="px-5 py-3 text-xs text-stone-500">{{ formatDate(col.collection_date) }}</td>
                                <td data-label="بواسطة" class="px-5 py-3 text-xs text-stone-500">{{ col.collectedByUser?.name ?? '—' }}</td>
                                <td data-label="ملاحظات" class="px-5 py-3 text-xs text-stone-500">{{ col.notes ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="collections.data.length === 0" class="py-12 text-center text-sm text-stone-500">لا توجد تحصيلات بعد</p>
                </div>
                <div
                    v-if="collections.last_page > 1"
                    class="flex items-center justify-between border-t border-stone-200 px-5 py-3 dark:border-stone-800"
                >
                    <p class="text-xs text-stone-500">إجمالي {{ collections.total }} سجل</p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            :disabled="collections.current_page === 1"
                            class="rounded-lg border border-stone-200 p-1.5 text-stone-500 transition hover:bg-stone-50 disabled:opacity-30 dark:border-stone-700"
                            @click="goCollectionPage(collections.current_page - 1)"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </button>
                        <span class="text-xs text-stone-500">{{ collections.current_page }} / {{ collections.last_page }}</span>
                        <button
                            type="button"
                            :disabled="collections.current_page === collections.last_page"
                            class="rounded-lg border border-stone-200 p-1.5 text-stone-500 transition hover:bg-stone-50 disabled:opacity-30 dark:border-stone-700"
                            @click="goCollectionPage(collections.current_page + 1)"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="activeTab === 'overdue'" class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="flex items-center gap-2 text-lg font-black text-stone-900 dark:text-white">
                    <AlertTriangle class="h-5 w-5 text-red-500" />
                    المطاعم المتأخرة عن السداد
                </h2>
                <button
                    v-if="overdueRestaurants.length > 0 && $can('billing.manage')"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-red-700"
                    @click="doAutoLock"
                >
                    <Lock class="h-4 w-4" />
                    قفل الكل
                </button>
            </div>

            <div
                v-if="overdueRestaurants.length === 0"
                class="rounded-3xl border border-emerald-200 bg-emerald-50 p-8 text-center dark:border-emerald-900/40 dark:bg-emerald-950/30"
            >
                <CheckCircle2 class="mx-auto mb-3 h-12 w-12 text-emerald-600" />
                <p class="text-lg font-black text-emerald-700 dark:text-emerald-300">لا توجد مطاعم متأخرة</p>
                <p class="mt-1 text-sm text-stone-500">كل الفواتير المفتوحة ما زالت داخل مهلة السداد</p>
            </div>
            <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div
                    v-for="r in overdueRestaurants"
                    :key="r.id"
                    class="flex items-center justify-between gap-4 rounded-3xl border border-red-200 bg-white p-5 dark:border-red-900/40 dark:bg-stone-900"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-950/50">
                            <Store class="h-5 w-5" />
                        </div>
                        <div>
                            <p class="font-black text-stone-900 dark:text-white">{{ r.name }}</p>
                            <p class="mt-0.5 text-xs font-bold text-red-600">{{ r.overdue_invoices_count }} فاتورة متأخرة</p>
                            <p class="text-xs text-stone-500">
                                {{ r.status === 'SUSPENDED' ? 'موقوف' : r.status === 'ACTIVE' ? 'نشط' : r.status }}
                            </p>
                        </div>
                    </div>
                    <button
                        v-if="r.status !== 'SUSPENDED' && $can('billing.manage')"
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-red-700"
                        @click="lockRestaurant(r)"
                    >
                        <Lock class="h-3.5 w-3.5" />
                        قفل
                    </button>
                    <span
                        v-else
                        class="rounded-xl border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300"
                    >
                        مقفل
                    </span>
                </div>
            </div>
        </div>

        <div v-if="editingInvoice" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-stone-950/70 backdrop-blur-sm" @click="editingInvoice = null" />
            <div class="relative z-10 w-full max-w-md space-y-4 rounded-3xl border border-stone-200 bg-white p-6 shadow-2xl dark:border-stone-800 dark:bg-stone-900">
                <div class="flex items-center justify-between border-b border-stone-100 pb-3 dark:border-stone-800">
                    <div>
                        <h3 class="font-black text-stone-900 dark:text-white">تعديل الفاتورة</h3>
                        <p class="font-mono text-xs text-stone-500">
                            {{ editingInvoice.invoice_number }} — {{ editingInvoice.restaurant.name }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-1 text-stone-400 transition hover:bg-stone-100 hover:text-stone-900 dark:hover:bg-stone-800 dark:hover:text-white"
                        @click="editingInvoice = null"
                    >
                        <XCircle class="h-5 w-5" />
                    </button>
                </div>

                <form class="space-y-3.5" @submit.prevent="submitEditInvoice">
                    <div>
                        <label class="mb-1 block text-xs font-bold text-stone-600 dark:text-stone-300">نوع الفاتورة *</label>
                        <select v-model="editForm.invoice_type" :class="fieldClass">
                            <option value="SUBSCRIPTION">اشتراك شهري</option>
                            <option value="COMMISSION">عمولة مبيعات</option>
                            <option value="MANUAL">يدوي</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-stone-600 dark:text-stone-300">المبلغ (ج.م) *</label>
                        <input v-model="editForm.subtotal" type="number" step="0.01" required :class="fieldClass" />
                        <p v-if="editForm.errors.subtotal" class="mt-1 text-xs text-red-600">{{ editForm.errors.subtotal }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-stone-600 dark:text-stone-300">تاريخ الاستحقاق *</label>
                        <input v-model="editForm.due_date" type="date" required :class="fieldClass" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-stone-600 dark:text-stone-300">ملاحظات</label>
                        <textarea
                            v-model="editForm.notes"
                            rows="2"
                            placeholder="أي تفاصيل إضافية"
                            :class="fieldClass + ' resize-none'"
                        />
                    </div>
                    <div class="flex gap-2 pt-2">
                        <button
                            type="button"
                            class="flex-1 rounded-xl border border-stone-200 px-4 py-2.5 text-sm font-bold text-stone-600 transition hover:bg-stone-50 dark:border-stone-700 dark:text-stone-300"
                            @click="editingInvoice = null"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="flex-1 rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-orange-700 disabled:opacity-50"
                        >
                            {{ editForm.processing ? 'جاري الحفظ...' : 'حفظ التعديلات' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
