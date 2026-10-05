<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, Clock, Download } from '@lucide/vue';

interface Invoice {
    id: number;
    invoice_number: string;
    period_start: string;
    period_end: string;
    subtotal_amount: number;
    tax_amount: number;
    total_amount: number;
    status: string;
    due_date: string | null;
    paid_at: string | null;
    issued_at: string | null;
    created_at: string;
    restaurant: { id: number; name: string };
}

const props = defineProps<{
    invoice: Invoice;
}>();

const statusMap: Record<string, { label: string; cls: string }> = {
    DRAFT: { label: 'مسودة', cls: 'bg-stone-500/20 text-stone-400 border-stone-500/30' },
    ISSUED: { label: 'صادرة', cls: 'bg-indigo-500/20 text-indigo-400 border-indigo-500/30' },
    PAID: { label: 'مدفوعة', cls: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' },
    OVERDUE: { label: 'متأخرة', cls: 'bg-red-500/20 text-red-400 border-red-500/30' },
};

const fmt = (v: number): string => (v / 100).toFixed(2);
const sc = computed(() => statusMap[props.invoice.status] ?? statusMap.DRAFT);
</script>

<template>
    <Head :title="`فاتورة ${invoice.invoice_number}`" />

    <div class="max-w-3xl" dir="rtl">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <Link
                    href="/admin/invoices"
                    class="p-2 rounded-lg bg-white hover:bg-stone-100 text-stone-400 hover:text-stone-900 transition-colors"
                >
                    <ArrowLeft class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-stone-900">فاتورة {{ invoice.invoice_number }}</h1>
                    <p class="text-stone-400 text-sm mt-1">{{ invoice.restaurant.name }}</p>
                </div>
                <span :class="['px-3 py-1 rounded-full text-xs font-semibold border', sc.cls]">{{ sc.label }}</span>
            </div>
            <a
                :href="`/admin/invoices/${invoice.id}/pdf`"
                class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-medium transition-colors"
            >
                <Download class="w-4 h-4" />
                تحميل PDF
            </a>
        </div>

        <div class="bg-white border border-stone-200 rounded-2xl p-8 space-y-6 shadow-xs">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-orange-400">وصلة</h2>
                    <p class="text-stone-400 text-sm mt-1">منصة توصيل الطعام</p>
                </div>
                <div class="text-right">
                    <p class="text-xl font-bold text-stone-900">{{ invoice.invoice_number }}</p>
                    <p class="text-stone-400 text-sm mt-1">
                        تاريخ الإصدار: {{ invoice.issued_at ? new Date(invoice.issued_at).toLocaleDateString('ar-EG') : '—' }}
                    </p>
                    <p v-if="invoice.due_date" class="text-stone-400 text-sm">
                        تاريخ الاستحقاق: {{ new Date(invoice.due_date).toLocaleDateString('ar-EG') }}
                    </p>
                </div>
            </div>

            <hr class="border-stone-200" />

            <div class="grid grid-cols-2 gap-8">
                <div>
                    <p class="text-xs text-stone-500 uppercase mb-2">إلى</p>
                    <p class="text-stone-900 font-semibold">{{ invoice.restaurant.name }}</p>
                </div>
                <div>
                    <p class="text-xs text-stone-500 uppercase mb-2">الفترة</p>
                    <p class="text-stone-900">
                        {{ new Date(invoice.period_start).toLocaleDateString('ar-EG') }} — {{ new Date(invoice.period_end).toLocaleDateString('ar-EG') }}
                    </p>
                </div>
            </div>

            <hr class="border-stone-200" />

            <div>
                <table class="record-cards w-full text-sm">
                    <thead>
                        <tr class="text-stone-400 border-b border-stone-200">
                            <th class="text-right pb-3 font-medium">البند</th>
                            <th class="text-right pb-3 font-medium">المبلغ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr>
                            <td data-label="البند" class="is-title py-3 text-stone-200">عمولة المنصة عن الفترة</td>
                            <td data-label="المبلغ" class="py-3 text-stone-900 font-medium">{{ fmt(invoice.subtotal_amount) }} ج</td>
                        </tr>
                        <tr>
                            <td data-label="البند" class="is-title py-3 text-stone-200">ضريبة القيمة المضافة</td>
                            <td data-label="المبلغ" class="py-3 text-stone-900 font-medium">{{ fmt(invoice.tax_amount) }} ج</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <hr class="border-stone-200" />

            <div class="flex items-center justify-between">
                <span class="text-xl font-bold text-stone-900">الإجمالي</span>
                <span class="text-3xl font-bold text-orange-400">{{ fmt(invoice.total_amount) }} ج</span>
            </div>

            <div
                v-if="invoice.paid_at"
                class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 flex items-center gap-3"
            >
                <CheckCircle class="w-5 h-5 text-emerald-400 shrink-0" />
                <p class="text-emerald-300 text-sm">تم السداد في {{ new Date(invoice.paid_at).toLocaleDateString('ar-EG') }}</p>
            </div>
            <div
                v-if="invoice.status === 'OVERDUE'"
                class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 flex items-center gap-3"
            >
                <Clock class="w-5 h-5 text-red-400 shrink-0" />
                <p class="text-red-300 text-sm">الفاتورة متأخرة — يرجى السداد فوراً</p>
            </div>
        </div>
    </div>
</template>
