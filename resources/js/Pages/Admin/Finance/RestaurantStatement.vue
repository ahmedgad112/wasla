<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowRight, RefreshCw, Wallet } from '@lucide/vue';
import { subscriptionPlanLabel } from '../../../lib/subscriptionPlans';

interface RestaurantStatement {
    id: number;
    name: string;
    status: string;
    commission_type: string;
    commission_percentage: number;
    monthly_subscription_fee: number;
    billing_cycle: string;
    grace_period_days: number;
    subscription_starts_at?: string | null;
    subscription_ends_at?: string | null;
    payment_due_date?: string | null;
}

interface RenewalWindow {
    billing_cycle: string;
    starts_at: string;
    ends_at: string;
    due_date: string;
    grace_days: number;
    plan_label: string;
}

interface StatementInvoice {
    id: number;
    invoice_number: string;
    invoice_type: string;
    status: string;
    issue_date?: string | null;
    due_date?: string | null;
    total_amount: number;
    paid_amount: number;
    notes?: string | null;
}

interface StatementCollection {
    id: number;
    amount: number;
    payment_method: string;
    collection_date?: string | null;
    notes?: string | null;
    invoice_number?: string | null;
}

const props = defineProps<{
    restaurant: RestaurantStatement;
    renewal: RenewalWindow;
    invoices: StatementInvoice[];
    collections: StatementCollection[];
    dues: { subscription: number; commission: number; total: number };
}>();

const onPercentagePlan = props.restaurant.commission_type === 'PERCENTAGE' && props.restaurant.monthly_subscription_fee <= 0;

const form = useForm({
    billing_model: (onPercentagePlan ? 'percentage' : 'subscription') as 'subscription' | 'percentage',
    price_mode: 'same' as 'same' | 'custom',
    amount: props.restaurant.monthly_subscription_fee > 0 ? props.restaurant.monthly_subscription_fee : '',
    pay_full: true,
    collected_amount: '' as number | '',
    commission_rate: props.restaurant.commission_percentage > 0 ? props.restaurant.commission_percentage : 15,
    payment_method: 'CASH',
});

const paymentMethods = [
    { value: 'CASH', label: 'نقدي' },
    { value: 'BANK_TRANSFER', label: 'تحويل بنكي' },
    { value: 'VODAFONE_CASH', label: 'فودافون كاش' },
    { value: 'INSTAPAY', label: 'إنستاباي' },
];

const openInvoices = computed(() =>
    props.invoices.filter((invoice) => invoice.status !== 'PAID' && invoice.total_amount - invoice.paid_amount > 0.009),
);

const collectionForm = useForm({
    invoice_id: (openInvoices.value[0]?.id ?? '') as number | '',
    amount: '' as number | '',
    payment_method: 'CASH',
    notes: '',
});

const selectedInvoice = computed(() =>
    openInvoices.value.find((invoice) => invoice.id === Number(collectionForm.invoice_id)) ?? null,
);

const remainingAmount = computed(() => {
    if (!selectedInvoice.value) {
        return 0;
    }

    return Math.round((selectedInvoice.value.total_amount - selectedInvoice.value.paid_amount) * 100) / 100;
});

const payFullAmount = (): void => {
    collectionForm.amount = remainingAmount.value;
};

const paymentMethodLabel = (method: string): string =>
    paymentMethods.find((item) => item.value === method)?.label ?? method;

const submitCollection = (): void => {
    collectionForm.post(`/admin/finance/restaurants/${props.restaurant.id}/collections`, {
        preserveScroll: true,
    });
};

const renewalAmount = computed(() =>
    form.price_mode === 'same' ? props.restaurant.monthly_subscription_fee : Number(form.amount || 0),
);

const collectedNow = computed(() =>
    form.pay_full ? renewalAmount.value : Number(form.collected_amount || 0),
);

const fmt = (value: number): string =>
    Number(value || 0).toLocaleString('ar-EG', { maximumFractionDigits: 2 });

const invoiceTypeLabel = (type: string): string => (type === 'SUBSCRIPTION' ? 'اشتراك' : 'عمولة');

const statusLabel = (status: string): string => {
    if (status === 'PAID') {
        return 'مدفوعة';
    }
    if (status === 'PARTIALLY_PAID') {
        return 'مدفوعة جزئياً';
    }
    if (status === 'OVERDUE') {
        return 'متأخرة';
    }

    return 'غير محصّلة';
};

const submitRenewal = (): void => {
    form.transform((data) => ({
        ...data,
        collected_amount: data.billing_model === 'subscription'
            ? (data.pay_full ? renewalAmount.value : data.collected_amount)
            : null,
    })).post(`/admin/finance/restaurants/${props.restaurant.id}/renew-subscription`);
};
</script>

<template>
    <Head :title="`كشف حساب ${restaurant.name}`" />

    <div class="mx-auto max-w-5xl space-y-6" dir="rtl">
        <div class="flex items-center gap-3">
            <Link
                href="/admin/finance"
                class="rounded-xl border border-stone-200 bg-white p-2 text-stone-500 hover:bg-stone-50 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-300"
            >
                <ArrowRight class="h-5 w-5" />
            </Link>
            <div>
                <h1 class="text-2xl font-black text-stone-900 dark:text-white">كشف حساب {{ restaurant.name }}</h1>
                <p class="mt-1 text-sm text-stone-500">العمولات المستحقة وتجديد الاشتراك</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-stone-800 dark:bg-stone-900">
                <template v-if="restaurant.commission_type === 'PERCENTAGE' && restaurant.monthly_subscription_fee <= 0">
                    <p class="text-xs text-stone-400">نسبة من كل طلب</p>
                    <p class="mt-1 text-xl font-black text-stone-900 dark:text-white">
                        {{ fmt(restaurant.commission_percentage) }}%
                    </p>
                    <p class="mt-1 text-[11px] text-stone-400">بتتخصم من قيمة كل طلب</p>
                </template>
                <template v-else>
                    <p class="text-xs text-stone-400">اشتراك {{ subscriptionPlanLabel(restaurant.billing_cycle) }}</p>
                    <p class="mt-1 text-xl font-black text-stone-900 dark:text-white">
                        {{ fmt(restaurant.monthly_subscription_fee) }} ج.م
                    </p>
                    <p class="mt-1 text-[11px] text-stone-400">
                        ينتهي {{ restaurant.subscription_ends_at || 'غير محدد' }}
                        ثم {{ restaurant.grace_period_days }} يوم سماح
                    </p>
                </template>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                <p class="text-xs text-amber-700 dark:text-amber-300">عمولات مستحقة</p>
                <p class="mt-1 text-xl font-black text-amber-800 dark:text-amber-200">{{ fmt(dues.commission) }} ج.م</p>
            </div>
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/30">
                <p class="text-xs text-red-700 dark:text-red-300">إجمالي غير المحصّل</p>
                <p class="mt-1 text-xl font-black text-red-700 dark:text-red-300">{{ fmt(dues.total) }} ج.م</p>
                <p class="mt-1 text-[11px] text-red-600/80">منه اشتراك {{ fmt(dues.subscription) }} ج.م</p>
            </div>
        </div>

        <form class="rounded-3xl border border-stone-200 bg-white p-6 shadow-xs dark:border-stone-800 dark:bg-stone-900" @submit.prevent="submitCollection">
            <h2 class="flex items-center gap-2 text-lg font-black text-stone-900 dark:text-white">
                <Wallet class="h-5 w-5 text-emerald-500" />
                تسجيل تحصيل
            </h2>
            <p class="mt-1 text-xs text-stone-500">سجّل المبلغ كله أو جزءاً منه. لو في فاتورة مفتوحة اربط التحصيل بيها، ولو مفيش سجّله لوحده.</p>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-bold text-stone-600 dark:text-stone-300">الفاتورة</label>
                    <select
                        v-model="collectionForm.invoice_id"
                        class="w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 focus:border-orange-500 focus:outline-none dark:border-stone-700 dark:bg-stone-950 dark:text-white"
                    >
                        <option value="">بدون فاتورة</option>
                        <option v-for="invoice in openInvoices" :key="invoice.id" :value="invoice.id">
                            {{ invoice.invoice_number }} — متبقي {{ fmt(invoice.total_amount - invoice.paid_amount) }} ج.م
                        </option>
                    </select>
                    <p v-if="collectionForm.errors.invoice_id" class="mt-1 text-xs text-red-500">{{ collectionForm.errors.invoice_id }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-stone-600 dark:text-stone-300">طريقة الدفع</label>
                    <select
                        v-model="collectionForm.payment_method"
                        class="w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 focus:border-orange-500 focus:outline-none dark:border-stone-700 dark:bg-stone-950 dark:text-white"
                    >
                        <option v-for="method in paymentMethods" :key="method.value" :value="method.value">{{ method.label }}</option>
                    </select>
                    <p v-if="collectionForm.errors.payment_method" class="mt-1 text-xs text-red-500">{{ collectionForm.errors.payment_method }}</p>
                </div>
                <div>
                    <div class="mb-1 flex items-center justify-between gap-2">
                        <label class="text-sm font-bold text-stone-600 dark:text-stone-300">المبلغ</label>
                        <button
                            v-if="selectedInvoice"
                            type="button"
                            class="text-xs font-bold text-orange-600 hover:text-orange-500"
                            @click="payFullAmount"
                        >
                            المبلغ كله ({{ fmt(remainingAmount) }} ج.م)
                        </button>
                    </div>
                    <input
                        v-model.number="collectionForm.amount"
                        type="number"
                        min="0.01"
                        step="0.01"
                        class="w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 focus:border-orange-500 focus:outline-none dark:border-stone-700 dark:bg-stone-950 dark:text-white"
                    />
                    <p v-if="collectionForm.errors.amount" class="mt-1 text-xs text-red-500">{{ collectionForm.errors.amount }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-bold text-stone-600 dark:text-stone-300">ملاحظة</label>
                    <input
                        v-model="collectionForm.notes"
                        type="text"
                        placeholder="اختياري"
                        class="w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 focus:border-orange-500 focus:outline-none dark:border-stone-700 dark:bg-stone-950 dark:text-white"
                    />
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button
                    type="submit"
                    :disabled="collectionForm.processing"
                    class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-black text-white hover:bg-emerald-500 disabled:opacity-50"
                >
                    {{ collectionForm.processing ? 'جارٍ التسجيل...' : 'تسجيل التحصيل' }}
                </button>
            </div>
        </form>

        <form class="rounded-3xl border border-stone-200 bg-white p-6 shadow-xs dark:border-stone-800 dark:bg-stone-900" @submit.prevent="submitRenewal">
            <h2 class="flex items-center gap-2 text-lg font-black text-stone-900 dark:text-white">
                <RefreshCw class="h-5 w-5 text-orange-500" />
                تجديد الحساب
            </h2>
            <p class="mt-1 text-xs text-stone-500">
                اختَر تكملة الاشتراك أو التحويل لنسبة من كل طلب.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                <button
                    type="button"
                    :class="[
                        'rounded-2xl border-2 p-4 text-right',
                        form.billing_model === 'subscription'
                            ? 'border-orange-500 bg-orange-50 text-orange-950 dark:bg-orange-950/30 dark:text-orange-100'
                            : 'border-stone-200 bg-stone-50 text-stone-600 dark:border-stone-700 dark:bg-stone-950',
                    ]"
                    @click="form.billing_model = 'subscription'"
                >
                    <p class="text-sm font-black">اشتراك</p>
                    <p class="mt-1 text-xs">رسوم عن المدة، ومفيش نسبة على الطلبات</p>
                </button>
                <button
                    type="button"
                    :class="[
                        'rounded-2xl border-2 p-4 text-right',
                        form.billing_model === 'percentage'
                            ? 'border-orange-500 bg-orange-50 text-orange-950 dark:bg-orange-950/30 dark:text-orange-100'
                            : 'border-stone-200 bg-stone-50 text-stone-600 dark:border-stone-700 dark:bg-stone-950',
                    ]"
                    @click="form.billing_model = 'percentage'"
                >
                    <p class="text-sm font-black">نسبة من كل طلب</p>
                    <p class="mt-1 text-xs">تتخصم من قيمة الطلبات، من غير فاتورة اشتراك</p>
                </button>
            </div>
            <p v-if="form.errors.billing_model" class="mt-2 text-xs text-red-500">{{ form.errors.billing_model }}</p>

            <template v-if="form.billing_model === 'subscription'">
                <p class="mt-4 text-xs text-stone-500">
                    الفترة الجديدة من {{ renewal.starts_at }} إلى {{ renewal.ends_at }}، والإيقاف بعد {{ renewal.due_date }}
                    (سماح {{ renewal.grace_days }} يوم). المبلغ المدفوع يتسجل في الأرباح والتحصيل.
                </p>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <button
                        type="button"
                        :class="[
                            'rounded-2xl border-2 p-4 text-right',
                            form.price_mode === 'same'
                                ? 'border-orange-500 bg-orange-50 text-orange-950 dark:bg-orange-950/30 dark:text-orange-100'
                                : 'border-stone-200 bg-stone-50 text-stone-600 dark:border-stone-700 dark:bg-stone-950',
                        ]"
                        @click="form.price_mode = 'same'"
                    >
                        <p class="text-sm font-black">بنفس السعر</p>
                        <p class="mt-1 text-xs">{{ fmt(restaurant.monthly_subscription_fee) }} ج.م</p>
                    </button>
                    <button
                        type="button"
                        :class="[
                            'rounded-2xl border-2 p-4 text-right',
                            form.price_mode === 'custom'
                                ? 'border-orange-500 bg-orange-50 text-orange-950 dark:bg-orange-950/30 dark:text-orange-100'
                                : 'border-stone-200 bg-stone-50 text-stone-600 dark:border-stone-700 dark:bg-stone-950',
                        ]"
                        @click="form.price_mode = 'custom'"
                    >
                        <p class="text-sm font-black">تغيير السعر</p>
                        <p class="mt-1 text-xs">سعر جديد لهذه الفترة والفترات الجاية</p>
                    </button>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div v-if="form.price_mode === 'custom'">
                        <label class="mb-1 block text-sm font-bold text-stone-600 dark:text-stone-300">السعر الجديد (ج.م)</label>
                        <input
                            v-model.number="form.amount"
                            type="number"
                            min="0.01"
                            step="0.01"
                            class="w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 focus:border-orange-500 focus:outline-none dark:border-stone-700 dark:bg-stone-950 dark:text-white"
                        />
                        <p v-if="form.errors.amount" class="mt-1 text-xs text-red-500">{{ form.errors.amount }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-bold text-stone-600 dark:text-stone-300">طريقة الدفع</label>
                        <select
                            v-model="form.payment_method"
                            class="w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 focus:border-orange-500 focus:outline-none dark:border-stone-700 dark:bg-stone-950 dark:text-white"
                        >
                            <option v-for="method in paymentMethods" :key="method.value" :value="method.value">
                                {{ method.label }}
                            </option>
                        </select>
                        <p v-if="form.errors.payment_method" class="mt-1 text-xs text-red-500">{{ form.errors.payment_method }}</p>
                    </div>
                    <div>
                        <div class="mb-1 flex items-center justify-between gap-2">
                            <label class="text-sm font-bold text-stone-600 dark:text-stone-300">المحصّل الآن</label>
                            <button type="button" class="text-xs font-bold text-orange-600 hover:text-orange-500" @click="form.pay_full = true; form.collected_amount = ''">
                                الفاتورة كلها ({{ fmt(renewalAmount) }} ج.م)
                            </button>
                        </div>
                        <input
                            v-model.number="form.collected_amount"
                            type="number"
                            min="0.01"
                            step="0.01"
                            :max="renewalAmount"
                            :placeholder="form.pay_full ? String(renewalAmount) : 'جزء من الفاتورة'"
                            class="w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 focus:border-orange-500 focus:outline-none dark:border-stone-700 dark:bg-stone-950 dark:text-white"
                            @input="form.pay_full = false"
                        />
                        <p v-if="form.pay_full" class="mt-1 text-xs text-stone-500">هيتسجل سداد الفاتورة كاملة.</p>
                        <p v-else class="mt-1 text-xs text-stone-500">المتبقي {{ fmt(Math.max(renewalAmount - collectedNow, 0)) }} ج.م يفضل مستحق على الفاتورة.</p>
                        <p v-if="form.errors.collected_amount" class="mt-1 text-xs text-red-500">{{ form.errors.collected_amount }}</p>
                    </div>
                </div>
                <p v-if="form.errors.price_mode" class="mt-2 text-xs text-red-500">{{ form.errors.price_mode }}</p>
            </template>

            <div v-else class="mt-4 max-w-xs">
                <label class="mb-1 block text-sm font-bold text-stone-600 dark:text-stone-300">النسبة من كل طلب (%)</label>
                <input
                    v-model.number="form.commission_rate"
                    type="number"
                    min="0"
                    max="100"
                    step="0.1"
                    class="w-full rounded-xl border border-stone-200 bg-stone-50 px-4 py-2.5 text-stone-900 focus:border-orange-500 focus:outline-none dark:border-stone-700 dark:bg-stone-950 dark:text-white"
                />
                <p class="mt-1 text-xs text-stone-500">مثال: 15 يعني المنصة بتاخد 15% من قيمة كل طلب.</p>
                <p v-if="form.errors.commission_rate" class="mt-1 text-xs text-red-500">{{ form.errors.commission_rate }}</p>
            </div>

            <div class="mt-4 flex items-center justify-between gap-3">
                <p class="text-sm font-bold text-stone-700 dark:text-stone-200">
                    <template v-if="form.billing_model === 'subscription'">هيتحصّل {{ fmt(collectedNow) }} من فاتورة {{ fmt(renewalAmount) }} ج.م</template>
                    <template v-else>هتتخصم {{ fmt(Number(form.commission_rate || 0)) }}% من كل طلب</template>
                </p>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-xl bg-orange-500 px-5 py-2.5 text-sm font-black text-white hover:bg-orange-400 disabled:opacity-50"
                >
                    {{ form.processing ? 'جارٍ الحفظ...' : form.billing_model === 'subscription' ? 'تجديد وتسجيل الدفع' : 'حفظ النسبة' }}
                </button>
            </div>
        </form>

        <div class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
            <h2 class="mb-3 text-sm font-black text-stone-900 dark:text-white">الفواتير</h2>
            <div class="overflow-x-auto">
                <table class="record-cards w-full text-right text-sm">
                    <thead class="text-xs text-stone-400">
                        <tr>
                            <th class="py-2">الرقم</th>
                            <th class="py-2">النوع</th>
                            <th class="py-2">الحالة</th>
                            <th class="py-2">المبلغ</th>
                            <th class="py-2">المدفوع</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="invoice in invoices" :key="invoice.id" class="border-t border-stone-100 dark:border-stone-800">
                            <td data-label="الرقم" class="is-title py-2 font-mono text-xs">{{ invoice.invoice_number }}</td>
                            <td data-label="النوع" class="py-2">{{ invoiceTypeLabel(invoice.invoice_type) }}</td>
                            <td data-label="الحالة" class="py-2">{{ statusLabel(invoice.status) }}</td>
                            <td data-label="المبلغ" class="py-2 font-bold">{{ fmt(invoice.total_amount) }}</td>
                            <td data-label="المدفوع" class="py-2">{{ fmt(invoice.paid_amount) }}</td>
                        </tr>
                        <tr v-if="invoices.length === 0">
                            <td colspan="5" class="py-6 text-center text-stone-400">لا توجد فواتير</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-3xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
            <h2 class="mb-3 text-sm font-black text-stone-900 dark:text-white">التحصيلات</h2>
            <div class="space-y-2">
                <div
                    v-for="collection in collections"
                    :key="collection.id"
                    class="flex items-center justify-between rounded-xl bg-stone-50 px-3 py-2 text-sm dark:bg-stone-950"
                >
                    <span>
                        {{ collection.collection_date }}
                        <template v-if="collection.invoice_number"> — {{ collection.invoice_number }}</template>
                        — {{ paymentMethodLabel(collection.payment_method) }}
                        <template v-if="collection.notes"> — {{ collection.notes }}</template>
                    </span>
                    <span class="font-black text-emerald-600">{{ fmt(collection.amount) }} ج.م</span>
                </div>
                <p v-if="collections.length === 0" class="py-4 text-center text-sm text-stone-400">لا توجد تحصيلات</p>
            </div>
        </div>
    </div>
</template>
