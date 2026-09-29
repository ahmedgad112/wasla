<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Zap,
    Lock,
    CheckCircle2,
    Clock,
    AlertTriangle,
    Plus,
    DollarSign,
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
    Settings2,
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
    overdueRestaurants: Restaurant[];
    restaurants: Restaurant[];
    filters: { inv_status?: string; restaurant_id?: string };
}>();

const invoiceStatusMap: Record<string, { label: string; cls: string }> = {
    DRAFT: { label: 'مسودة', cls: 'bg-stone-500/20 text-stone-400 border-stone-500/30' },
    ISSUED: { label: 'صادرة', cls: 'bg-blue-500/20 text-blue-400 border-blue-500/30' },
    PAID: { label: 'مدفوعة', cls: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' },
    PARTIALLY_PAID: { label: 'جزئياً', cls: 'bg-teal-500/20 text-teal-400 border-teal-500/30' },
    OVERDUE: { label: 'متأخرة', cls: 'bg-red-500/20 text-red-400 border-red-500/30' },
    CANCELLED: { label: 'ملغاة', cls: 'bg-stone-700/30 text-stone-500 border-stone-600/30' },
};

const invoiceTypeMap: Record<string, string> = {
    COMMISSION: 'عمولة',
    SUBSCRIPTION: 'اشتراك',
    MANUAL: 'يدوي',
    COMBINED: 'مجمّع',
};

const invoiceStatusFilters = ['', 'ISSUED', 'PAID', 'OVERDUE', 'PARTIALLY_PAID', 'CANCELLED'];

const fmt = (v: number | string): string => {
    const num = Number(v);
    if (isNaN(num)) {
        return '0';
    }
    return Number(num.toFixed(2)).toString();
};

const formatDate = (value: string): string => new Date(value).toLocaleDateString('ar-EG');

const invoiceRowStatus = (inv: Invoice): { label: string; cls: string } => {
    const isPaid = inv.status === 'PAID';
    const isCancelled = inv.status === 'CANCELLED';
    if (isPaid) {
        return invoiceStatusMap.PAID;
    }
    if (isCancelled) {
        return invoiceStatusMap.CANCELLED;
    }
    return invoiceStatusMap.OVERDUE;
};

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
    { id: 'overview' as const, label: 'لوحة التحكم', icon: Settings2 },
    { id: 'invoices' as const, label: 'الفواتير', icon: Receipt },
    { id: 'collections' as const, label: 'سجل التحصيل', icon: CreditCard },
    {
        id: 'overdue' as const,
        label: `المتأخرون (${props.overdueRestaurants.length})`,
        icon: AlertTriangle,
    },
]);

const openEditInvoice = (inv: Invoice): void => {
    editingInvoice.value = inv;
    editForm.setData({
        subtotal: String(inv.total_amount),
        due_date: inv.due_date,
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
    ask('سيتم قفل جميع حسابات المطاعم التي لديها فواتير لم تسدّد بعد. هل تريد المتابعة؟', () => {
        router.post('/admin/billing/auto-lock-overdue');
    }, true);

const doMarkPaid = (id: number, num: string): void =>
    ask(`هل تريد تسجيل الفاتورة ${num} كمدفوعة وإعادة تفعيل المطعم؟`, () => {
        router.post(`/admin/billing/invoice/${id}/mark-paid`);
    });

const doSuspend = (id: number, num: string): void =>
    ask(`هل تريد قفل المطعم بسبب عدم سداد الفاتورة ${num}؟`, () => {
        router.post(`/admin/billing/invoice/${id}/suspend`);
    }, true);

const doCancel = (id: number, num: string): void =>
    ask(`هل تريد إلغاء الفاتورة ${num}؟`, () => {
        router.post(`/admin/billing/invoice/${id}/cancel`);
    }, true);

const lockRestaurant = (r: Restaurant): void =>
    ask(`هل تريد قفل مطعم "${r.name}"؟`, () => router.post('/admin/billing/auto-lock-overdue'), true);

const submitCollection = (): void => {
    collectionForm.post('/admin/billing/collection', {
        onSuccess: () => {
            collectionForm.reset();
            showCollectionForm.value = false;
        },
    });
};

const submitInvoice = (): void => {
    invoiceForm.post('/admin/billing/invoice', {
        onSuccess: () => {
            invoiceForm.reset();
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
        <!-- ── Header ── -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-stone-900 flex items-center gap-2">
                    <Banknote class="w-7 h-7 text-orange-500" />
                    مركز التحصيل
                </h1>
                <p class="text-stone-400 text-sm mt-1">إدارة الفواتير والتحصيل وقفل الحسابات — كل شيء في مكان واحد</p>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <button
                    type="button"
                    class="flex items-center gap-2 px-4 py-2.5 bg-amber-500 hover:bg-amber-400 text-white rounded-xl text-sm font-bold transition shadow-lg shadow-amber-500/20"
                    @click="doAutoGenerate"
                >
                    <Zap class="w-4 h-4" />
                    توليد فواتير الشهر
                </button>
                <button
                    type="button"
                    class="flex items-center gap-2 px-4 py-2.5 bg-red-600 hover:bg-red-500 text-white rounded-xl text-sm font-bold transition shadow-lg shadow-red-500/20"
                    @click="doAutoLock"
                >
                    <Lock class="w-4 h-4" />
                    قفل المتأخرين تلقائياً
                </button>
            </div>
        </div>

        <!-- ── Stats Cards ── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-2xl border p-5 border-emerald-500/20 bg-emerald-500/10">
                <div class="mb-2"><TrendingUp class="w-5 h-5 text-emerald-400" /></div>
                <p class="text-2xl font-black text-stone-900">{{ fmt(stats.total_revenue) }} ج</p>
                <p class="text-stone-400 text-xs mt-1">إجمالي المحصّل</p>
            </div>
            <div class="rounded-2xl border p-5 border-amber-500/20 bg-amber-500/10">
                <div class="mb-2"><Clock class="w-5 h-5 text-amber-400" /></div>
                <p class="text-2xl font-black text-stone-900">{{ fmt(stats.total_pending) }} ج</p>
                <p class="text-stone-400 text-xs mt-1">في الانتظار</p>
            </div>
            <div class="rounded-2xl border p-5 border-red-500/20 bg-red-500/10">
                <div class="mb-2"><AlertTriangle class="w-5 h-5 text-red-400" /></div>
                <p class="text-2xl font-black text-stone-900">{{ stats.overdue_count }}</p>
                <p class="text-stone-400 text-xs mt-1">فواتير متأخرة</p>
            </div>
            <div class="rounded-2xl border p-5 border-indigo-500/20 bg-indigo-500/10">
                <div class="mb-2"><CreditCard class="w-5 h-5 text-indigo-400" /></div>
                <p class="text-2xl font-black text-stone-900">{{ fmt(stats.total_collected) }} ج</p>
                <p class="text-stone-400 text-xs mt-1">سندات التحصيل</p>
            </div>
        </div>

        <!-- ── Tabs ── -->
        <div class="flex gap-1 bg-white border border-stone-200 rounded-2xl p-1.5 overflow-x-auto shadow-xs">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                :class="[
                    'flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all',
                    activeTab === tab.id
                        ? 'bg-orange-600 text-white shadow-md shadow-orange-600/30'
                        : 'text-stone-400 hover:text-stone-900 hover:bg-stone-50',
                ]"
                @click="activeTab = tab.id"
            >
                <component :is="tab.icon" class="w-4 h-4 shrink-0" />
                {{ tab.label }}
            </button>
        </div>

        <!-- ─────────────────────────────── TAB: Overview ── -->
        <div v-if="activeTab === 'overview'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                <h2 class="text-stone-900 font-bold flex items-center gap-2">
                    <RefreshCw class="w-5 h-5 text-orange-400" />
                    العمليات التلقائية
                </h2>
                <p class="text-stone-400 text-sm">اضغط الزر لتنفيذ العمليات على جميع المطاعم دفعة واحدة</p>

                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-4 bg-white rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5"><Zap class="w-5 h-5 text-amber-400" /></div>
                            <div>
                                <p class="text-stone-900 font-semibold text-sm">توليد فواتير الشهر الحالي</p>
                                <p class="text-stone-400 text-xs mt-0.5">يُنشئ فاتورة لكل مطعم لم يتم إصدار فاتورة له هذا الشهر بعد</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 px-3 py-1.5 rounded-lg text-white text-xs font-bold transition bg-amber-500 hover:bg-amber-400"
                            @click="doAutoGenerate"
                        >
                            تنفيذ
                        </button>
                    </div>
                    <div class="flex items-center justify-between gap-4 bg-white rounded-xl p-4">
                        <div class="flex items-start gap-3">
                            <div class="mt-0.5"><Lock class="w-5 h-5 text-red-400" /></div>
                            <div>
                                <p class="text-stone-900 font-semibold text-sm">قفل الحسابات المتأخرة</p>
                                <p class="text-stone-400 text-xs mt-0.5">يوقف كل المطاعم التي تجاوزت تاريخ استحقاق الفاتورة ولم تسدّد</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 px-3 py-1.5 rounded-lg text-white text-xs font-bold transition bg-red-600 hover:bg-red-500"
                            @click="doAutoLock"
                        >
                            قفل الآن
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <h2 class="text-stone-900 font-bold flex items-center gap-2">
                        <DollarSign class="w-5 h-5 text-emerald-400" />
                        تسجيل تحصيل سريع
                    </h2>
                    <button
                        type="button"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition"
                        @click="showCollectionForm = !showCollectionForm"
                    >
                        <Plus class="w-3.5 h-3.5" />
                        تسجيل
                    </button>
                </div>

                <form v-if="showCollectionForm" class="space-y-3" @submit.prevent="submitCollection">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs text-stone-400 mb-1">المطعم *</label>
                            <select
                                v-model="collectionForm.restaurant_id"
                                class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-stone-900 text-sm focus:outline-none focus:border-orange-500"
                            >
                                <option value="">اختر المطعم</option>
                                <option v-for="r in restaurants" :key="r.id" :value="r.id">{{ r.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-stone-400 mb-1">المبلغ (ج.م) *</label>
                            <input
                                v-model="collectionForm.amount"
                                type="number"
                                step="0.01"
                                placeholder="0.00"
                                class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-stone-900 text-sm focus:outline-none focus:border-orange-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs text-stone-400 mb-1">طريقة الدفع *</label>
                            <select
                                v-model="collectionForm.payment_method"
                                class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-stone-900 text-sm focus:outline-none focus:border-orange-500"
                            >
                                <option value="CASH">نقدي</option>
                                <option value="BANK_TRANSFER">تحويل بنكي</option>
                                <option value="VODAFONE_CASH">فودافون كاش</option>
                                <option value="INSTAPAY">انستاباي</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-stone-400 mb-1">تاريخ التحصيل</label>
                            <input
                                v-model="collectionForm.collection_date"
                                type="date"
                                class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-stone-900 text-sm focus:outline-none focus:border-orange-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs text-stone-400 mb-1">ملاحظات</label>
                            <input
                                v-model="collectionForm.notes"
                                type="text"
                                placeholder="اختياري"
                                class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-stone-900 text-sm focus:outline-none focus:border-orange-500"
                            />
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            class="px-3 py-1.5 bg-white text-stone-300 rounded-lg text-xs transition hover:bg-stone-100"
                            @click="showCollectionForm = false"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="collectionForm.processing"
                            class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition disabled:opacity-50"
                        >
                            {{ collectionForm.processing ? 'جاري الحفظ...' : 'حفظ التحصيل' }}
                        </button>
                    </div>
                </form>

                <p v-if="!showCollectionForm" class="text-stone-500 text-sm text-center py-4">
                    اضغط "تسجيل" لإضافة سند تحصيل جديد
                </p>
            </div>

            <div
                v-if="overdueRestaurants.length > 0"
                class="col-span-full bg-red-500/10 border border-red-500/20 rounded-2xl p-5"
            >
                <h3 class="text-red-400 font-bold mb-3 flex items-center gap-2">
                    <AlertTriangle class="w-5 h-5" />
                    تحذير — {{ overdueRestaurants.length }} مطعم لم يسدّد مستحقاته
                </h3>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="r in overdueRestaurants"
                        :key="r.id"
                        class="px-3 py-1 bg-red-500/20 text-red-300 text-xs font-medium rounded-full border border-red-500/30"
                    >
                        {{ r.name }} ({{ r.overdue_invoices_count }} فاتورة)
                    </span>
                </div>
                <button
                    type="button"
                    class="mt-4 flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-xl text-sm font-bold transition"
                    @click="doAutoLock"
                >
                    <Lock class="w-4 h-4" />
                    قفل جميعهم الآن
                </button>
            </div>
        </div>

        <!-- ─────────────────────────────── TAB: Invoices ── -->
        <div v-if="activeTab === 'invoices'" class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2 flex-wrap">
                    <button
                        v-for="s in invoiceStatusFilters"
                        :key="s || 'all'"
                        type="button"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-semibold transition',
                            (filters.inv_status || '') === s
                                ? 'bg-orange-600 text-white'
                                : 'bg-stone-100 text-stone-500 hover:text-stone-900 hover:bg-stone-200',
                        ]"
                        @click="filterInvoices(s)"
                    >
                        {{ s ? invoiceStatusMap[s]?.label : 'الكل' }}
                    </button>
                </div>
                <button
                    type="button"
                    class="flex items-center gap-2 px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold transition"
                    @click="showInvoiceForm = !showInvoiceForm"
                >
                    <Plus class="w-4 h-4" />
                    فاتورة جديدة
                </button>
            </div>

            <div v-if="showInvoiceForm" class="bg-white border border-stone-200 rounded-2xl p-5 shadow-xs">
                <h3 class="text-stone-900 font-bold mb-4">إصدار فاتورة مخصصة</h3>
                <form class="grid grid-cols-2 md:grid-cols-4 gap-3" @submit.prevent="submitInvoice">
                    <div>
                        <label class="block text-xs text-stone-400 mb-1">المطعم *</label>
                        <select
                            v-model="invoiceForm.restaurant_id"
                            class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-stone-900 text-sm focus:outline-none focus:border-orange-500"
                        >
                            <option v-for="r in restaurants" :key="r.id" :value="r.id">{{ r.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-stone-400 mb-1">النوع *</label>
                        <select
                            v-model="invoiceForm.invoice_type"
                            class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-stone-900 text-sm focus:outline-none focus:border-orange-500"
                        >
                            <option value="SUBSCRIPTION">اشتراك شهري</option>
                            <option value="COMMISSION">عمولة</option>
                            <option value="MANUAL">يدوي</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-stone-400 mb-1">المبلغ (ج.م) *</label>
                        <input
                            v-model="invoiceForm.subtotal"
                            type="number"
                            step="0.01"
                            placeholder="0.00"
                            class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-stone-900 text-sm focus:outline-none focus:border-orange-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs text-stone-400 mb-1">تاريخ الاستحقاق *</label>
                        <input
                            v-model="invoiceForm.due_date"
                            type="date"
                            class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-stone-900 text-sm focus:outline-none focus:border-orange-500"
                        />
                    </div>
                    <div class="col-span-2 md:col-span-4 flex justify-end gap-2">
                        <button
                            type="button"
                            class="px-4 py-2 bg-white text-stone-300 rounded-lg text-xs transition hover:bg-stone-100"
                            @click="showInvoiceForm = false"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="invoiceForm.processing"
                            class="px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-lg text-xs font-bold transition disabled:opacity-50"
                        >
                            {{ invoiceForm.processing ? 'جاري الإصدار...' : 'إصدار الفاتورة' }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white border border-stone-200 rounded-2xl overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-stone-200">
                            <tr class="text-stone-400 text-right">
                                <th class="px-5 py-3 font-medium">رقم الفاتورة</th>
                                <th class="px-5 py-3 font-medium">المطعم</th>
                                <th class="px-5 py-3 font-medium">النوع</th>
                                <th class="px-5 py-3 font-medium">المبلغ</th>
                                <th class="px-5 py-3 font-medium">المدفوع</th>
                                <th class="px-5 py-3 font-medium">الحالة</th>
                                <th class="px-5 py-3 font-medium">الاستحقاق</th>
                                <th class="px-5 py-3 font-medium">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr
                                v-for="inv in invoices.data"
                                :key="inv.id"
                                :class="[
                                    'hover:bg-stone-50 transition-colors',
                                    inv.status !== 'PAID' && inv.status !== 'CANCELLED' ? 'bg-red-500/5' : '',
                                ]"
                            >
                                <td class="px-5 py-3 text-indigo-400 font-mono text-xs">{{ inv.invoice_number }}</td>
                                <td class="px-5 py-3 text-stone-900 font-medium">{{ inv.restaurant.name }}</td>
                                <td class="px-5 py-3 text-stone-400 text-xs">
                                    {{ invoiceTypeMap[inv.invoice_type] ?? inv.invoice_type }}
                                </td>
                                <td class="px-5 py-3 text-stone-900 font-semibold">{{ fmt(inv.total_amount) }} ج</td>
                                <td class="px-5 py-3 text-emerald-400">{{ fmt(inv.paid_amount) }} ج</td>
                                <td class="px-5 py-3">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-xs font-bold border',
                                            invoiceRowStatus(inv).cls,
                                        ]"
                                    >
                                        {{ invoiceRowStatus(inv).label }}
                                    </span>
                                </td>
                                <td
                                    :class="[
                                        'px-5 py-3 text-xs',
                                        inv.status !== 'PAID' && inv.status !== 'CANCELLED'
                                            ? 'text-red-400 font-bold'
                                            : 'text-stone-400',
                                    ]"
                                >
                                    {{ formatDate(inv.due_date) }}
                                    <template v-if="inv.status !== 'PAID' && inv.status !== 'CANCELLED'"> ⚠️</template>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <button
                                            v-if="inv.status !== 'PAID'"
                                            type="button"
                                            title="تعديل الفاتورة"
                                            class="p-1.5 rounded-lg bg-blue-500/20 hover:bg-blue-500/40 text-blue-400 transition"
                                            @click="openEditInvoice(inv)"
                                        >
                                            <Pencil class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            v-if="inv.status !== 'PAID' && inv.status !== 'CANCELLED'"
                                            type="button"
                                            title="تسجيل كمدفوعة"
                                            class="p-1.5 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/40 text-emerald-400 transition"
                                            @click="doMarkPaid(inv.id, inv.invoice_number)"
                                        >
                                            <CheckCircle2 class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            v-if="inv.status !== 'PAID' && inv.status !== 'CANCELLED'"
                                            type="button"
                                            title="قفل المطعم"
                                            class="p-1.5 rounded-lg bg-red-500/20 hover:bg-red-500/40 text-red-400 transition"
                                            @click="doSuspend(inv.id, inv.invoice_number)"
                                        >
                                            <ShieldOff class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            v-if="inv.status !== 'PAID' && inv.status !== 'CANCELLED'"
                                            type="button"
                                            title="إلغاء الفاتورة"
                                            class="p-1.5 rounded-lg bg-stone-500/20 hover:bg-stone-500/40 text-stone-400 transition"
                                            @click="doCancel(inv.id, inv.invoice_number)"
                                        >
                                            <XCircle class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="invoices.data.length === 0" class="text-stone-500 text-center py-12 text-sm">لا توجد فواتير</p>
                </div>
                <div
                    v-if="invoices.last_page > 1"
                    class="flex items-center justify-between px-5 py-3 border-t border-stone-200"
                >
                    <p class="text-stone-400 text-xs">إجمالي {{ invoices.total }} سجل</p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            :disabled="invoices.current_page === 1"
                            class="p-1.5 rounded-lg bg-white hover:bg-stone-100 text-stone-400 disabled:opacity-30 transition"
                            @click="goInvoicePage(invoices.current_page - 1)"
                        >
                            <ChevronRight class="w-4 h-4" />
                        </button>
                        <span class="text-stone-400 text-xs">{{ invoices.current_page }} / {{ invoices.last_page }}</span>
                        <button
                            type="button"
                            :disabled="invoices.current_page === invoices.last_page"
                            class="p-1.5 rounded-lg bg-white hover:bg-stone-100 text-stone-400 disabled:opacity-30 transition"
                            @click="goInvoicePage(invoices.current_page + 1)"
                        >
                            <ChevronLeft class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─────────────────────────────── TAB: Collections ── -->
        <div v-if="activeTab === 'collections'" class="space-y-4">
            <div class="flex justify-between items-center">
                <h2 class="text-stone-900 font-bold text-lg">سجل التحصيلات</h2>
                <button
                    type="button"
                    class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition"
                    @click="showCollectionForm = !showCollectionForm"
                >
                    <Plus class="w-4 h-4" />
                    تسجيل تحصيل
                </button>
            </div>

            <div v-if="showCollectionForm" class="bg-white border border-stone-200 rounded-2xl p-5 shadow-xs">
                <h3 class="text-stone-900 font-bold mb-4">تسجيل سند تحصيل جديد</h3>
                <form class="grid grid-cols-2 md:grid-cols-3 gap-3" @submit.prevent="submitCollection">
                    <div class="col-span-2 md:col-span-1">
                        <label class="block text-xs text-stone-400 mb-1">المطعم *</label>
                        <select
                            v-model="collectionForm.restaurant_id"
                            class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-stone-900 text-sm focus:outline-none focus:border-orange-500"
                        >
                            <option value="">اختر المطعم</option>
                            <option v-for="r in restaurants" :key="r.id" :value="r.id">{{ r.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-stone-400 mb-1">المبلغ (ج.م) *</label>
                        <input
                            v-model="collectionForm.amount"
                            type="number"
                            step="0.01"
                            placeholder="0.00"
                            class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-stone-900 text-sm focus:outline-none focus:border-orange-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs text-stone-400 mb-1">طريقة الدفع *</label>
                        <select
                            v-model="collectionForm.payment_method"
                            class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-stone-900 text-sm focus:outline-none focus:border-orange-500"
                        >
                            <option value="CASH">نقدي</option>
                            <option value="BANK_TRANSFER">تحويل بنكي</option>
                            <option value="VODAFONE_CASH">فودافون كاش</option>
                            <option value="INSTAPAY">انستاباي</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-stone-400 mb-1">تاريخ التحصيل</label>
                        <input
                            v-model="collectionForm.collection_date"
                            type="date"
                            class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-stone-900 text-sm focus:outline-none focus:border-orange-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs text-stone-400 mb-1">ملاحظات</label>
                        <input
                            v-model="collectionForm.notes"
                            type="text"
                            placeholder="اختياري"
                            class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-stone-900 text-sm focus:outline-none focus:border-orange-500"
                        />
                    </div>
                    <div class="col-span-2 md:col-span-3 flex justify-end gap-2">
                        <button
                            type="button"
                            class="px-4 py-2 bg-white text-stone-300 rounded-lg text-xs transition hover:bg-stone-100"
                            @click="showCollectionForm = false"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="collectionForm.processing"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition disabled:opacity-50"
                        >
                            {{ collectionForm.processing ? 'جاري الحفظ...' : 'حفظ التحصيل' }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white border border-stone-200 rounded-2xl overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-stone-200">
                            <tr class="text-stone-400 text-right">
                                <th class="px-5 py-3 font-medium">المطعم</th>
                                <th class="px-5 py-3 font-medium">المبلغ</th>
                                <th class="px-5 py-3 font-medium">طريقة الدفع</th>
                                <th class="px-5 py-3 font-medium">تاريخ التحصيل</th>
                                <th class="px-5 py-3 font-medium">بواسطة</th>
                                <th class="px-5 py-3 font-medium">ملاحظات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr
                                v-for="col in collections.data"
                                :key="col.id"
                                class="hover:bg-white transition-colors"
                            >
                                <td class="px-5 py-3 text-stone-900 font-medium">{{ col.restaurant.name }}</td>
                                <td class="px-5 py-3 text-emerald-400 font-bold">{{ fmt(col.amount) }} ج</td>
                                <td class="px-5 py-3 text-stone-400 text-xs">{{ col.payment_method }}</td>
                                <td class="px-5 py-3 text-stone-400 text-xs">{{ formatDate(col.collection_date) }}</td>
                                <td class="px-5 py-3 text-stone-400 text-xs">{{ col.collectedByUser?.name ?? '—' }}</td>
                                <td class="px-5 py-3 text-stone-500 text-xs">{{ col.notes ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="collections.data.length === 0" class="text-stone-500 text-center py-12 text-sm">
                        لا توجد تحصيلات بعد
                    </p>
                </div>
                <div
                    v-if="collections.last_page > 1"
                    class="flex items-center justify-between px-5 py-3 border-t border-stone-200"
                >
                    <p class="text-stone-400 text-xs">إجمالي {{ collections.total }} سجل</p>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            :disabled="collections.current_page === 1"
                            class="p-1.5 rounded-lg bg-white hover:bg-stone-100 text-stone-400 disabled:opacity-30 transition"
                            @click="goCollectionPage(collections.current_page - 1)"
                        >
                            <ChevronRight class="w-4 h-4" />
                        </button>
                        <span class="text-stone-400 text-xs">
                            {{ collections.current_page }} / {{ collections.last_page }}
                        </span>
                        <button
                            type="button"
                            :disabled="collections.current_page === collections.last_page"
                            class="p-1.5 rounded-lg bg-white hover:bg-stone-100 text-stone-400 disabled:opacity-30 transition"
                            @click="goCollectionPage(collections.current_page + 1)"
                        >
                            <ChevronLeft class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─────────────────────────────── TAB: Overdue ── -->
        <div v-if="activeTab === 'overdue'" class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-stone-900 font-bold text-lg flex items-center gap-2">
                    <AlertTriangle class="w-5 h-5 text-red-400" />
                    المطاعم المتأخرة عن السداد
                </h2>
                <button
                    v-if="overdueRestaurants.length > 0"
                    type="button"
                    class="flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-xl text-xs font-bold transition"
                    @click="doAutoLock"
                >
                    <Lock class="w-4 h-4" />
                    قفل الكل تلقائياً
                </button>
            </div>

            <div
                v-if="overdueRestaurants.length === 0"
                class="bg-emerald-500/10 border border-emerald-500/20 rounded-2xl p-8 text-center"
            >
                <CheckCircle2 class="w-12 h-12 text-emerald-400 mx-auto mb-3" />
                <p class="text-emerald-400 font-bold text-lg">ممتاز!</p>
                <p class="text-stone-400 text-sm mt-1">لا توجد مطاعم متأخرة عن السداد حالياً</p>
            </div>
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div
                    v-for="r in overdueRestaurants"
                    :key="r.id"
                    class="bg-white border border-red-500/20 rounded-2xl p-5 flex items-center justify-between gap-4"
                >
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-500/20 rounded-xl flex items-center justify-center">
                            <Store class="w-5 h-5 text-red-400" />
                        </div>
                        <div>
                            <p class="text-stone-900 font-bold">{{ r.name }}</p>
                            <p class="text-red-400 text-xs mt-0.5">{{ r.overdue_invoices_count }} فاتورة متأخرة</p>
                            <p class="text-stone-500 text-xs">
                                الحالة:
                                {{
                                    r.status === 'SUSPENDED'
                                        ? '🔒 موقوف'
                                        : r.status === 'ACTIVE'
                                          ? '🟢 نشط'
                                          : r.status
                                }}
                            </p>
                        </div>
                    </div>
                    <button
                        v-if="r.status !== 'SUSPENDED'"
                        type="button"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-red-600 hover:bg-red-500 text-white rounded-lg text-xs font-bold transition"
                        @click="lockRestaurant(r)"
                    >
                        <Lock class="w-3.5 h-3.5" />
                        قفل
                    </button>
                    <span
                        v-else
                        class="px-3 py-1.5 bg-red-500/20 text-red-400 rounded-lg text-xs font-bold border border-red-500/30"
                    >
                        🔒 مقفل
                    </span>
                </div>
            </div>
        </div>

        <!-- ── Edit Invoice Modal ── -->
        <div
            v-if="editingInvoice"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
            @click="editingInvoice = null"
        >
            <div
                class="bg-stone-900 border border-stone-200 rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4"
                @click.stop
            >
                <div class="flex items-center justify-between border-b border-stone-200 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center text-blue-400">
                            <Pencil class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="text-stone-900 font-bold text-base">تعديل الفاتورة</h3>
                            <p class="text-stone-400 text-xs font-mono">
                                {{ editingInvoice.invoice_number }} — {{ editingInvoice.restaurant.name }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="p-1 rounded-lg text-stone-400 hover:text-stone-900 hover:bg-white transition"
                        @click="editingInvoice = null"
                    >
                        <XCircle class="w-5 h-5" />
                    </button>
                </div>

                <form class="space-y-3.5" @submit.prevent="submitEditInvoice">
                    <div>
                        <label class="block text-xs text-stone-400 mb-1">نوع الفاتورة *</label>
                        <select
                            v-model="editForm.invoice_type"
                            class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-stone-900 text-sm focus:outline-none focus:border-blue-500"
                        >
                            <option value="SUBSCRIPTION">اشتراك شهري</option>
                            <option value="COMMISSION">عمولة مبيعات</option>
                            <option value="MANUAL">يدوي</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs text-stone-400 mb-1">المبلغ (ج.م) *</label>
                        <input
                            v-model="editForm.subtotal"
                            type="number"
                            step="0.01"
                            required
                            class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-stone-900 text-sm focus:outline-none focus:border-blue-500 font-mono"
                        />
                    </div>

                    <div>
                        <label class="block text-xs text-stone-400 mb-1">تاريخ الاستحقاق *</label>
                        <input
                            v-model="editForm.due_date"
                            type="date"
                            required
                            class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-stone-900 text-sm focus:outline-none focus:border-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs text-stone-400 mb-1">ملاحظات</label>
                        <textarea
                            v-model="editForm.notes"
                            rows="2"
                            placeholder="أي تفاصيل أو ملاحظات إضافية..."
                            class="w-full bg-white border border-stone-200 rounded-lg px-3 py-2 text-stone-900 text-sm focus:outline-none focus:border-blue-500 resize-none"
                        />
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button
                            type="button"
                            class="flex-1 px-4 py-2.5 bg-white hover:bg-stone-100 text-stone-300 rounded-xl text-sm transition"
                            @click="editingInvoice = null"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-sm font-bold transition disabled:opacity-50 flex items-center justify-center gap-1.5"
                        >
                            {{ editForm.processing ? 'جاري الحفظ...' : 'حفظ التعديلات' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
