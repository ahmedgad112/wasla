<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import type { PaginatedResponse } from '../../../Types';
import { subscriptionPeriodLabel, subscriptionPlanLabel } from '../../../lib/subscriptionPlans';
import {
    Receipt,
    CheckCircle2,
    Clock,
    PhoneCall,
    MessageCircle,
    Copy,
    Check,
    CreditCard,
    DollarSign,
    ShieldAlert,
    Calendar,
} from '@lucide/vue';

interface InvoiceItem {
    id: number;
    description: string;
    amount: number;
}

interface Invoice {
    id: number;
    invoice_number: string;
    issue_date: string;
    due_date: string;
    total_amount: number;
    paid_amount: number;
    status: 'ISSUED' | 'PAID' | 'OVERDUE' | 'CANCELLED';
    invoice_type: string;
    notes?: string;
    items?: InvoiceItem[];
}

const props = withDefaults(
    defineProps<{
        restaurant: {
            id: number;
            name: string;
            status: 'ACTIVE' | 'INACTIVE' | 'SUSPENDED' | 'PENDING';
            commission_type: string;
            commission_percentage?: number;
            monthly_subscription_fee?: number;
            billing_cycle: string;
            grace_period_days?: number | null;
            subscription_ends_at?: string | null;
            payment_due_date?: string;
            billing_suspended_at?: string;
            suspension_reason?: string;
        };
        invoices: PaginatedResponse<Invoice>;
        pendingInvoice?: Invoice | null;
        daysUntilDue?: number | null;
        totalPaid: number;
        supportPhone?: string;
    }>(),
    {
        pendingInvoice: null,
        daysUntilDue: null,
        supportPhone: '01027961208',
    },
);

const items = computed(() => props.invoices?.data || []);
const copied = ref(false);

const isSuspended = computed(() => props.restaurant.status === 'SUSPENDED');
const isDueSoon = computed(
    () =>
        props.daysUntilDue !== null &&
        props.daysUntilDue !== undefined &&
        props.daysUntilDue >= 0 &&
        props.daysUntilDue <= 5 &&
        !isSuspended.value &&
        !!props.pendingInvoice,
);

const isSubscription = computed(() => {
    const r = props.restaurant;
    return (
        r.commission_type === 'SUBSCRIPTION' ||
        r.commission_type === 'MONTHLY_SUBSCRIPTION' ||
        (!!r.monthly_subscription_fee && r.monthly_subscription_fee > 0 && !r.commission_percentage)
    );
});

const cleanPhone = computed(() => props.supportPhone.replace(/[^0-9]/g, ''));
const whatsappUrl = computed(() => {
    const phone = cleanPhone.value.startsWith('0') ? cleanPhone.value.substring(1) : cleanPhone.value;
    return `https://wa.me/2${phone}?text=${encodeURIComponent(
        `مرحباً، أود الاستفسار بخصوص فواتير وتفعيل مطعم: ${props.restaurant.name}`,
    )}`;
});

const copyPhone = (): void => {
    navigator.clipboard.writeText(props.supportPhone);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
};

const dueDateLabel = computed(() => {
    if (!props.pendingInvoice) {
        const days = typeof props.daysUntilDue === 'number' && props.daysUntilDue > 0 ? props.daysUntilDue : 30;
        const grace =
            typeof props.restaurant.grace_period_days === 'number' && props.restaurant.grace_period_days > 0
                ? ` شاملة ${props.restaurant.grace_period_days} يوم سماح`
                : '';
        return `الاشتراك مسدد ✅ (التجديد أو الإيقاف بعد ${days} يوم${grace})`;
    }
    if (typeof props.daysUntilDue !== 'number') {
        return 'دورة السداد: شهرية';
    }
    if (props.daysUntilDue > 0) {
        return `متبقي ${props.daysUntilDue} يوم على الاستحقاق`;
    }
    if (props.daysUntilDue === 0) {
        return 'اليوم هو موعد الاستحقاق';
    }
    return `متأخر منذ ${Math.abs(props.daysUntilDue)} يوم`;
});
</script>

<template>
    <Head title="الفواتير والاشتراكات — بوابة المطعم" />

    <div class="space-y-6 max-w-7xl mx-auto">
        <div
            v-if="isSuspended"
            class="p-6 rounded-3xl bg-gradient-to-r from-red-600 via-rose-600 to-red-700 text-white shadow-xl shadow-red-500/20 border-2 border-red-400/30 animate-pulse"
        >
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <div class="p-3 bg-white/20 rounded-2xl shrink-0 backdrop-blur-sm">
                        <ShieldAlert class="w-8 h-8 text-stone-900" />
                    </div>
                    <div>
                        <h2 class="text-lg font-black tracking-wide flex items-center gap-2">
                            <span>تنبيه عاجل: تم إيقاف حساب المطعم مؤقتاً لعدم سداد المستحقات!</span>
                        </h2>
                        <p class="text-xs text-red-100 mt-1 leading-relaxed max-w-3xl">
                            تم إيقاف ظهور مطعمك للعملاء على منصة وصلة وإيقاف حسابات كباتن التوصيل التابعين لك حتى إتمام سداد الفاتورة المستحقة.
                            يرجى مراجعة المبلغ المطلوب أدناه والتواصل الفوري مع الدعم الفني لإعادة التنشيط.
                        </p>
                        <div
                            v-if="restaurant.suspension_reason"
                            class="mt-2 text-xs font-bold bg-stone-100 px-3 py-1.5 rounded-xl inline-block"
                        >
                            السبب المسجل: {{ restaurant.suspension_reason }}
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0 w-full md:w-auto">
                    <a
                        :href="whatsappUrl"
                        target="_blank"
                        rel="noreferrer"
                        class="flex-1 md:flex-initial px-5 py-3 rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white font-black text-xs shadow-lg flex items-center justify-center gap-2 transition"
                    >
                        <MessageCircle class="w-4 h-4" />
                        <span>تواصل واتساب لتنشيط الحساب</span>
                    </a>
                    <a
                        :href="`tel:${supportPhone}`"
                        class="px-4 py-3 rounded-2xl bg-white text-red-700 hover:bg-stone-100 font-black text-xs shadow-lg flex items-center justify-center gap-2 transition"
                    >
                        <PhoneCall class="w-4 h-4" />
                        <span>اتصال</span>
                    </a>
                </div>
            </div>
        </div>

        <div
            v-if="!isSuspended && isDueSoon"
            class="p-5 rounded-3xl bg-amber-500 text-stone-950 border border-amber-400 shadow-md flex items-center justify-between gap-4 flex-wrap"
        >
            <div class="flex items-center gap-3">
                <Clock class="w-6 h-6 text-stone-950 shrink-0" />
                <div>
                    <h3 class="text-sm font-black">تذكير بموعد سداد الفاتورة الشهرية</h3>
                    <p class="text-xs font-medium text-stone-900 mt-0.5">
                        متبقي {{ daysUntilDue }} أيام على موعد الاستحقاق ({{ restaurant.payment_due_date }}). يرجى السداد لتجنب توقف الحساب المؤقت.
                    </p>
                </div>
            </div>
            <div class="font-mono font-black text-sm bg-stone-950 text-amber-400 px-3 py-1.5 rounded-xl">
                مستحق: {{ Number(pendingInvoice?.total_amount || 0).toLocaleString() }} ج.م
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-stone-400 font-bold">نظام التعاقد</span>
                    <CreditCard class="w-5 h-5 text-orange-500" />
                </div>
                <div class="text-base font-black text-stone-900 dark:text-white">
                    <template v-if="isSubscription || (restaurant.monthly_subscription_fee && restaurant.monthly_subscription_fee > 0)">
                        اشتراك {{ subscriptionPlanLabel(restaurant.billing_cycle) }}
                    </template>
                    <template v-else-if="restaurant.commission_type === 'PERCENTAGE'">
                        نسبة عمولة (%{{ restaurant.commission_percentage || 0 }})
                    </template>
                    <template v-else>حسب الاتفاق</template>
                </div>
                <p class="text-[11px] text-stone-400 mt-1">
                    <template v-if="restaurant.monthly_subscription_fee && restaurant.monthly_subscription_fee > 0">
                        {{ Number(restaurant.monthly_subscription_fee).toLocaleString() }} ج.م / {{ subscriptionPeriodLabel(restaurant.billing_cycle) }}
                        <template v-if="restaurant.grace_period_days">
                            + {{ restaurant.grace_period_days }} يوم سماح
                        </template>
                        <template v-if="restaurant.commission_type === 'PERCENTAGE' && restaurant.commission_percentage">
                            + عمولة {{ restaurant.commission_percentage }}%
                        </template>
                    </template>
                    <template v-else>نسبة مقتطعة من إجمالي مبيعات الطلبات</template>
                </p>
            </div>

            <div class="bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-stone-400 font-bold">حالة الحساب</span>
                    <DollarSign class="w-5 h-5 text-emerald-500" />
                </div>
                <div class="flex items-center gap-2">
                    <span
                        v-if="isSuspended"
                        class="px-3 py-1 rounded-full text-xs font-black bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-400 inline-flex items-center gap-1.5"
                    >
                        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse" />
                        موقوف مؤقتاً
                    </span>
                    <span
                        v-else
                        class="px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 inline-flex items-center gap-1.5"
                    >
                        <span class="w-2 h-2 rounded-full bg-emerald-500" />
                        نشط ويعمل
                    </span>
                </div>
                <p class="text-[11px] text-stone-400 mt-1.5">
                    {{ isSuspended ? 'يرجى سداد الفاتورة لإعادة التشغيل' : 'المطعم متاح للطلب على المنصة' }}
                </p>
            </div>

            <div class="bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-stone-400 font-bold">موعد السداد القادم</span>
                    <Calendar class="w-5 h-5 text-indigo-500" />
                </div>
                <div class="text-base font-black font-mono text-stone-900 dark:text-white">
                    {{ restaurant.payment_due_date ? restaurant.payment_due_date.split('T')[0] : 'غير محدد' }}
                </div>
                <p class="text-[11px] text-stone-400 mt-1">
                    {{ dueDateLabel }}
                </p>
            </div>

            <div class="bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-stone-400 font-bold">إجمالي المسدد سابقاً</span>
                    <CheckCircle2 class="w-5 h-5 text-emerald-500" />
                </div>
                <div class="text-base font-black text-stone-900 dark:text-white">
                    {{ Number(totalPaid).toLocaleString() }} ج.م
                </div>
                <p class="text-[11px] text-stone-400 mt-1">
                    كل الفواتير السابقة المسددة
                </p>
            </div>
        </div>

        <div class="bg-gradient-to-br from-stone-900 to-stone-950 text-white rounded-3xl p-6 border border-stone-800 shadow-lg">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-500/20 text-orange-400 text-xs font-bold border border-orange-500/30 mb-1">
                        <PhoneCall class="w-3.5 h-3.5" />
                        <span>قسم التحصيل والدعم الفني</span>
                    </div>
                    <h3 class="text-base font-black">طرق السداد والدعم الفني للمطاعم</h3>
                    <p class="text-xs text-stone-400 leading-relaxed max-w-2xl">
                        يتم سداد المستحقات عبر المحافظ الإلكترونية (فودافون كاش / إنستاباي) أو التحويل البنكي.
                        بعد التحويل يرجى إرسال الإشعار لرقم الدعم الفني ليتم تأكيد التحصيل وتفعيل الحساب فورياً.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto">
                    <button
                        class="px-4 py-2.5 rounded-2xl bg-stone-800 hover:bg-stone-700 text-stone-200 font-mono text-xs font-bold flex items-center justify-center gap-2 border border-stone-700 transition"
                        @click="copyPhone"
                    >
                        <Check v-if="copied" class="w-4 h-4 text-emerald-400" />
                        <Copy v-else class="w-4 h-4" />
                        <span dir="ltr">{{ supportPhone }}</span>
                    </button>

                    <a
                        :href="whatsappUrl"
                        target="_blank"
                        rel="noreferrer"
                        class="px-4 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center justify-center gap-2 shadow-md transition"
                    >
                        <MessageCircle class="w-4 h-4" />
                        <span>واتساب الدعم</span>
                    </a>

                    <a
                        :href="`tel:${supportPhone}`"
                        class="px-4 py-2.5 rounded-2xl bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold flex items-center justify-center gap-2 shadow-md transition"
                    >
                        <PhoneCall class="w-4 h-4" />
                        <span>اتصال هاتفي</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-6 shadow-xs">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-stone-100 dark:border-stone-800">
                <div>
                    <h2 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-2">
                        <Receipt class="w-5 h-5 text-orange-500" />
                        <span>سجل الفواتير والمطالبات</span>
                    </h2>
                    <p class="text-xs text-stone-400 mt-0.5">
                        كافة الفواتير الشهرية الصادرة لمطعمك وتفاصيل سدادها
                    </p>
                </div>
            </div>

            <div v-if="items.length === 0" class="text-center py-16">
                <Receipt class="w-12 h-12 text-stone-300 dark:text-stone-600 mx-auto mb-2" />
                <p class="text-xs text-stone-400">لا توجد فواتير مسجلة لمطعمك حتى الآن.</p>
            </div>
            <div v-else class="overflow-x-auto">
                <table class="record-cards w-full text-right text-xs">
                    <thead>
                        <tr class="border-b border-stone-200 dark:border-stone-800 text-stone-400 text-[11px] font-bold">
                            <th class="py-3 px-4">رقم الفاتورة</th>
                            <th class="py-3 px-4">تاريخ الإصدار</th>
                            <th class="py-3 px-4">النوع</th>
                            <th class="py-3 px-4">المبلغ المستحق</th>
                            <th class="py-3 px-4">تاريخ الاستحقاق</th>
                            <th class="py-3 px-4">حالة السداد</th>
                            <th class="py-3 px-4">ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        <tr
                            v-for="inv in items"
                            :key="inv.id"
                            class="hover:bg-stone-50 dark:hover:bg-stone-800/50"
                        >
                            <td data-label="رقم الفاتورة" class="is-title py-4 px-4 font-mono font-bold text-orange-600">
                                {{ inv.invoice_number }}
                            </td>
                            <td data-label="تاريخ الإصدار" class="py-4 px-4 text-stone-500 font-mono">
                                {{ inv.issue_date ? inv.issue_date.split('T')[0] : '—' }}
                            </td>
                            <td data-label="النوع" class="py-4 px-4 font-bold text-stone-700 dark:text-stone-300">
                                {{ inv.invoice_type === 'SUBSCRIPTION' ? 'اشتراك شهري' : 'عمولة مبيعات' }}
                            </td>
                            <td data-label="المبلغ المستحق" class="py-4 px-4 font-black text-stone-900 dark:text-white">
                                {{ Number(inv.total_amount).toLocaleString() }} ج.م
                            </td>
                            <td data-label="تاريخ الاستحقاق" class="py-4 px-4 font-mono text-stone-500">
                                {{ inv.due_date ? inv.due_date.split('T')[0] : '—' }}
                            </td>
                            <td data-label="حالة السداد" class="py-4 px-4">
                                <span
                                    :class="[
                                        'px-2.5 py-1 rounded-full text-[10px] font-black inline-flex items-center gap-1',
                                        inv.status === 'PAID'
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'
                                            : inv.status === 'OVERDUE'
                                              ? 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300'
                                              : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300',
                                    ]"
                                >
                                    {{
                                        inv.status === 'PAID'
                                            ? 'تم السداد بنجاح ✅'
                                            : inv.status === 'OVERDUE'
                                              ? 'متأخرة عن السداد ⚠️'
                                              : 'بانتظار السداد'
                                    }}
                                </span>
                            </td>
                            <td data-label="ملاحظات" class="py-4 px-4 text-stone-400 text-[11px] max-w-xs truncate">
                                {{ inv.notes || '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
