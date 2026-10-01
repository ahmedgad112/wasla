<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import ConfirmModal from '../../../Components/ConfirmModal.vue';
import type { Invoice, Restaurant, PaginatedResponse } from '../../../Types';
import { Receipt, Plus, CheckCircle2, AlertTriangle, Zap } from '@lucide/vue';

const props = withDefaults(
    defineProps<{
        invoices: PaginatedResponse<Invoice>;
        restaurants?: Restaurant[];
        filters: { status?: string; restaurant_id?: string };
    }>(),
    {
        restaurants: () => [],
    },
);

const items = computed(() => props.invoices?.data || []);
const showCreateModal = ref(false);
const confirmPayId = ref<number | null>(null);
const confirmSuspendId = ref<number | null>(null);
const confirmCancelId = ref<number | null>(null);
const confirmAutoGenerate = ref(false);

const form = useForm({
    restaurant_id: props.restaurants[0]?.id ? String(props.restaurants[0].id) : '',
    invoice_type: 'COMMISSION',
    subtotal: '',
    due_date: new Date(Date.now() + 7 * 86400000).toISOString().split('T')[0],
    notes: '',
});

const handleCreateSubmit = (): void => {
    form.post('/admin/invoices', {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};

const onRestaurantChange = (): void => {
    const rId = form.restaurant_id;
    const found = props.restaurants.find((r) => String(r.id) === rId);
    if (found) {
        if (
            found.commission_type === 'SUBSCRIPTION' ||
            (found.monthly_subscription_fee && found.monthly_subscription_fee > 0)
        ) {
            form.restaurant_id = rId;
            form.invoice_type = 'SUBSCRIPTION';
            form.subtotal = String(found.monthly_subscription_fee || 100);
        }
    }
};

const confirmPay = (): void => {
    if (confirmPayId.value !== null) {
        router.patch(`/admin/invoices/${confirmPayId.value}/mark-paid`);
        confirmPayId.value = null;
    }
};

const confirmSuspend = (): void => {
    if (confirmSuspendId.value !== null) {
        router.patch(`/admin/invoices/${confirmSuspendId.value}/suspend-restaurant`);
        confirmSuspendId.value = null;
    }
};

const confirmCancel = (): void => {
    if (confirmCancelId.value !== null) {
        router.patch(`/admin/invoices/${confirmCancelId.value}/cancel`);
        confirmCancelId.value = null;
    }
};

const confirmGenerate = (): void => {
    router.post('/admin/invoices/auto-generate');
    confirmAutoGenerate.value = false;
};

const statusBadgeClass = (status: string): string => {
    if (status === 'PAID') {
        return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300';
    }
    if (status === 'OVERDUE') {
        return 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300';
    }
    return 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300';
};

const statusLabel = (status: string): string => {
    if (status === 'PAID') {
        return 'تم التحصيل';
    }
    if (status === 'OVERDUE') {
        return 'متأخرة عن السداد';
    }
    return 'بانتظار التحصيل';
};

const formatAmount = (amount: number | string): string => Number(amount).toLocaleString();
</script>

<template>
    <Head title="الفواتير والاشتراكات — الإدارة المركزية" />

    <div class="space-y-6">
        <div class="bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-6 shadow-xs">
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-stone-100 dark:border-stone-800"
            >
                <div>
                    <h1 class="text-xl font-black text-stone-900 dark:text-white flex items-center gap-2">
                        <Receipt class="w-6 h-6 text-orange-500" />
                        <span>فواتير العمولات والاشتراكات الشهرية</span>
                    </h1>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">
                        متابعة تحصيل الاشتراكات والعمولات وإيقاف/تفعيل حسابات المطاعم والكباتن تلقائياً
                    </p>
                </div>

                <div v-if="$can('billing.manage')" class="flex items-center gap-2 flex-wrap">
                    <button
                        type="button"
                        class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5"
                        title="توليد فواتير الشهر الحالي بناءً على الاشتراكات والنسب المحددة لكل مطعم"
                        @click="confirmAutoGenerate = true"
                    >
                        <Zap class="w-4 h-4" />
                        <span>توليد فواتير الشهر تلقائياً</span>
                    </button>

                    <button
                        type="button"
                        class="px-4 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs shadow-xs transition flex items-center gap-1.5"
                        @click="showCreateModal = true"
                    >
                        <Plus class="w-4 h-4" />
                        <span>إصدار فاتورة مخصصة</span>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2 mb-4 overflow-x-auto pb-2 text-xs">
                <span class="text-stone-400 font-bold ml-1">تصفية:</span>
                <Link
                    href="/admin/invoices"
                    :class="[
                        'px-3 py-1.5 rounded-xl font-bold transition',
                        !filters.status
                            ? 'bg-orange-500 text-white'
                            : 'bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300',
                    ]"
                >
                    الكل ({{ invoices.total || items.length }})
                </Link>
                <Link
                    href="/admin/invoices?status=ISSUED"
                    :class="[
                        'px-3 py-1.5 rounded-xl font-bold transition',
                        filters.status === 'ISSUED'
                            ? 'bg-amber-500 text-white'
                            : 'bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300',
                    ]"
                >
                    بانتظار السداد
                </Link>
                <Link
                    href="/admin/invoices?status=PAID"
                    :class="[
                        'px-3 py-1.5 rounded-xl font-bold transition',
                        filters.status === 'PAID'
                            ? 'bg-emerald-600 text-white'
                            : 'bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300',
                    ]"
                >
                    تم التحصيل
                </Link>
                <Link
                    href="/admin/invoices?status=OVERDUE"
                    :class="[
                        'px-3 py-1.5 rounded-xl font-bold transition',
                        filters.status === 'OVERDUE'
                            ? 'bg-red-600 text-white'
                            : 'bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300',
                    ]"
                >
                    متأخرة / معلقة
                </Link>
            </div>

            <div v-if="items.length === 0" class="text-center py-16">
                <Receipt class="w-14 h-14 text-stone-300 dark:text-stone-600 mx-auto mb-3" />
                <h3 class="text-sm font-bold text-stone-700 dark:text-stone-300">لا توجد فواتير مطابقة</h3>
                <p class="text-xs text-stone-400 mt-1">
                    اضغط على زر "توليد فواتير الشهر تلقائياً" أو أصدر فاتورة جديدة.
                </p>
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr
                            class="border-b border-stone-200 dark:border-stone-800 text-stone-400 text-[11px] font-bold"
                        >
                            <th class="py-3 px-4">رقم الفاتورة</th>
                            <th class="py-3 px-4">المطعم</th>
                            <th class="py-3 px-4">حالة المطعم</th>
                            <th class="py-3 px-4">نوع الفاتورة</th>
                            <th class="py-3 px-4">المبلغ</th>
                            <th class="py-3 px-4">تاريخ الاستحقاق</th>
                            <th class="py-3 px-4">حالة التحصيل</th>
                            <th class="py-3 px-4 text-center">الإجراء المالي والتنفيذي</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        <tr
                            v-for="inv in items"
                            :key="inv.id"
                            class="hover:bg-stone-50 dark:hover:bg-stone-800/50 transition"
                        >
                            <td class="py-4 px-4 font-mono font-bold text-orange-600">
                                {{ inv.invoice_number }}
                            </td>
                            <td class="py-4 px-4">
                                <div class="font-bold text-stone-900 dark:text-white">
                                    {{ inv.restaurant?.name || 'مطعم غير محدد' }}
                                </div>
                                <span v-if="inv.restaurant?.phone" class="text-[10px] text-stone-400 font-mono">
                                    {{ inv.restaurant.phone }}
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <span
                                    v-if="inv.restaurant?.status === 'SUSPENDED'"
                                    class="px-2.5 py-1 rounded-full text-[10px] font-black bg-red-100 dark:bg-red-950/60 text-red-700 dark:text-red-400 inline-flex items-center gap-1"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse" />
                                    معلق (غير نشط)
                                </span>
                                <span
                                    v-else
                                    class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 inline-flex items-center gap-1"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" />
                                    نشط
                                </span>
                            </td>
                            <td class="py-4 px-4 text-stone-600 dark:text-stone-400">
                                {{ inv.invoice_type === 'COMMISSION' ? 'عمولة مبيعات' : 'اشتراك شهري' }}
                            </td>
                            <td class="py-4 px-4 font-black text-stone-900 dark:text-white">
                                {{ formatAmount(inv.total_amount) }} ج.م
                            </td>
                            <td class="py-4 px-4 font-mono text-stone-500">{{ inv.due_date }}</td>
                            <td class="py-4 px-4">
                                <span
                                    :class="[
                                        'px-2.5 py-1 rounded-full text-[10px] font-bold',
                                        statusBadgeClass(inv.status),
                                    ]"
                                >
                                    {{ statusLabel(inv.status) }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5 flex-wrap">
                                    <template v-if="inv.status !== 'PAID' && inv.status !== 'CANCELLED' && $can('billing.manage')">
                                        <button
                                            type="button"
                                            class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-[11px] shadow-xs flex items-center gap-1 transition"
                                            title="تسجيل السداد وإعادة تفعيل المطعم والكباتن تلقائياً"
                                            @click="confirmPayId = inv.id"
                                        >
                                            <CheckCircle2 class="w-3.5 h-3.5" />
                                            <span>تم التحصيل ✅</span>
                                        </button>

                                        <button
                                            v-if="inv.restaurant?.status !== 'SUSPENDED'"
                                            type="button"
                                            class="px-2.5 py-1.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-[11px] shadow-xs flex items-center gap-1 transition"
                                            title="إيقاف حساب المطعم وكافة كباتن التوصيل لعدم السداد"
                                            @click="confirmSuspendId = inv.id"
                                        >
                                            <AlertTriangle class="w-3.5 h-3.5" />
                                            <span>تعليق الحساب</span>
                                        </button>

                                        <button
                                            type="button"
                                            class="px-2 py-1.5 rounded-xl border border-stone-200 dark:border-stone-700 text-stone-500 hover:text-red-600 text-[11px] transition"
                                            @click="confirmCancelId = inv.id"
                                        >
                                            إلغاء
                                        </button>
                                    </template>
                                    <div
                                        v-else-if="inv.status === 'PAID'"
                                        class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-bold text-[11px]"
                                    >
                                        <CheckCircle2 class="w-4 h-4" />
                                        <span>مكتمل ومسدد</span>
                                    </div>
                                    <span v-else class="text-stone-400 text-[11px]">ملغاة</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div
            class="fixed inset-0 bg-stone-950/70 backdrop-blur-sm"
            @click="showCreateModal = false"
        />
        <div
            class="relative bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 w-full max-w-md p-6 shadow-2xl z-10"
        >
            <h2 class="text-base font-black text-stone-900 dark:text-white mb-4 flex items-center gap-2">
                <Plus class="w-5 h-5 text-orange-500" />
                <span>إصدار فاتورة جديدة لمطعم</span>
            </h2>
            <form class="space-y-4" @submit.prevent="handleCreateSubmit">
                <div>
                    <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1"
                        >المطعم الشريك</label
                    >
                    <select
                        v-model="form.restaurant_id"
                        class="w-full p-2.5 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 font-bold"
                        @change="onRestaurantChange"
                    >
                        <option v-for="r in restaurants" :key="r.id" :value="r.id">
                            {{ r.name }} {{ r.status === 'SUSPENDED' ? '(معلق)' : '' }}
                        </option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1"
                            >نوع الفاتورة</label
                        >
                        <select
                            v-model="form.invoice_type"
                            class="w-full p-2.5 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700"
                        >
                            <option value="COMMISSION">عمولة مبيعات</option>
                            <option value="SUBSCRIPTION">اشتراك شهري</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1"
                            >المبلغ المطلوب (ج.م)</label
                        >
                        <input
                            v-model="form.subtotal"
                            type="number"
                            step="0.5"
                            required
                            placeholder="مثال: 500"
                            class="w-full p-2.5 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 font-bold"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1"
                        >آخر موعد للسداد (Due Date)</label
                    >
                    <input
                        v-model="form.due_date"
                        type="date"
                        required
                        class="w-full p-2.5 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700"
                    />
                </div>

                <div>
                    <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1"
                        >ملاحظات (اختياري)</label
                    >
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        placeholder="ملاحظات تظهر لمدير المطعم في الفاتورة..."
                        class="w-full p-2.5 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700"
                    />
                </div>

                <div class="flex gap-2 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs shadow transition"
                    >
                        إصدار الفاتورة فوراً
                    </button>
                    <button
                        type="button"
                        class="py-2.5 px-4 rounded-xl border border-stone-200 dark:border-stone-700 text-xs font-bold"
                        @click="showCreateModal = false"
                    >
                        إلغاء
                    </button>
                </div>
            </form>
        </div>
    </div>

    <ConfirmModal
        :is-open="confirmPayId !== null"
        title="تأكيد تحصيل الفاتورة وتفعيل الحساب"
        message="عند تسجيل هذه الفاتورة كمدفوعة، سيتم تلقائياً تفعيل حساب المطعم وجميع كباتن التوصيل التابعين له ليعودوا للعمل واستقبال الطلبات. هل تريد الاستمرار؟"
        confirm-text="نعم، تم التحصيل والتفعيل"
        cancel-text="إلغاء"
        variant="info"
        @confirm="confirmPay"
        @cancel="confirmPayId = null"
    />

    <ConfirmModal
        :is-open="confirmSuspendId !== null"
        title="تعليق حساب المطعم والكباتن لعدم السداد"
        message="تحذير: سيتم إيقاف هذا المطعم فوراً عن استقبال أي طلبات على المنصة، وسيتم إيقاف حسابات كافة كباتن التوصيل التابعين له، ولن يتمكنوا من العمل حتى يتم التحصيل. هل أنت متأكد؟"
        confirm-text="نعم، إيقاف المطعم والكباتن"
        cancel-text="تراجع"
        variant="danger"
        @confirm="confirmSuspend"
        @cancel="confirmSuspendId = null"
    />

    <ConfirmModal
        :is-open="confirmAutoGenerate"
        title="توليد فواتير الشهر الجديد تلقائياً"
        message="سيتم إصدار فاتورة جديدة لهذا الشهر لكل مطعم مسجل وفقاً لإعداداته المالية (الاشتراك الشهري أو نسبة المبيعات)، مع تحديد موعد استحقاق 7 أيام. هل تريد المتابعة؟"
        confirm-text="توليد الفواتير الآن"
        cancel-text="إلغاء"
        variant="info"
        @confirm="confirmGenerate"
        @cancel="confirmAutoGenerate = false"
    />

    <ConfirmModal
        :is-open="confirmCancelId !== null"
        title="إلغاء الفاتورة"
        message="هل أنت متأكد من إلغاء هذه الفاتورة؟ لن تكون قابلة للتحصيل بعد ذلك."
        confirm-text="نعم، ألغِ الفاتورة"
        cancel-text="تراجع"
        variant="danger"
        @confirm="confirmCancel"
        @cancel="confirmCancelId = null"
    />
</template>
