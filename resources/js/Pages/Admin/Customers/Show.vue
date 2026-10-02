<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    User,
    MapPin,
    Calendar,
    GraduationCap,
    XCircle,
    Phone,
    CheckCircle2,
    LogIn,
} from '@lucide/vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';

interface Customer {
    id: number;
    university_name?: string;
    university_id_number?: string;
    university_id_card_image?: string;
    student_status?: string;
    student_verified_at?: string;
    created_at: string;
    user: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
        is_active: boolean;
        created_at: string;
    };
    addresses: Array<{ id: number; label: string; address: string; is_default: boolean }>;
    orders: Array<{
        id: number;
        order_number: string;
        status: string;
        total_amount: number;
        created_at: string;
        restaurant: { name: string };
    }>;
}

interface CustomerOrder {
    id: number;
    order_number: string;
    status: string;
    total_amount: number;
    created_at: string;
    restaurant?: { name: string };
}

const props = withDefaults(
    defineProps<{
        customer: Customer;
        recent_orders?: CustomerOrder[];
    }>(),
    {
        recent_orders: () => [],
    },
);

const orders = computed((): CustomerOrder[] => {
    if (props.customer.orders?.length) {
        return props.customer.orders;
    }
    return props.recent_orders || [];
});
const isApprovedStudent = computed(() => props.customer.student_status === 'APPROVED');

const cardImageSrc = computed(() => {
    const img = props.customer.university_id_card_image;
    if (!img) {
        return null;
    }
    return img.startsWith('http') || img.startsWith('/') ? img : `/storage/${img}`;
});

const studentStatusLabel = computed(() => {
    if (props.customer.student_status === 'APPROVED') {
        return 'مفعل وموثق ✓';
    }
    if (props.customer.student_status === 'PENDING') {
        return 'قيد المراجعة ⏳';
    }
    return 'غير موثق';
});

const confirmLoginAs = ref(false);

const handleLoginAs = (): void => {
    confirmLoginAs.value = false;
    router.post(`/admin/customers/${props.customer.id}/login-as`);
};

const handleToggleActive = (): void => {
    router.post(`/admin/customers/${props.customer.id}/toggle-active`, {}, { preserveScroll: true });
};

const handleVerify = (): void => {
    router.post(`/admin/customers/${props.customer.id}/verify-student`);
};

const handleReject = (): void => {
    router.post(`/admin/customers/${props.customer.id}/reject-student`);
};

const formatDate = (date: string): string => new Date(date).toLocaleDateString('ar-EG');
</script>

<template>
    <Head :title="`${customer.user.name} — إدارة العملاء`" />

    <div class="space-y-6" dir="rtl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-stone-900 p-6 rounded-3xl border border-stone-200 dark:border-stone-800 shadow-xs">
            <div class="flex items-center gap-4">
                <Link
                    href="/admin/customers"
                    class="p-2.5 rounded-xl bg-stone-100 dark:bg-stone-800 hover:bg-stone-200 dark:hover:bg-stone-700 text-stone-600 dark:text-stone-300 transition-colors"
                >
                    <ArrowLeft class="w-5 h-5" />
                </Link>
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-xl font-black text-stone-900 dark:text-white">{{ customer.user.name }}</h1>
                        <span
                            v-if="isApprovedStudent"
                            class="flex items-center gap-1 px-3 py-1 bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 rounded-full text-xs font-bold"
                        >
                            <CheckCircle2 class="w-3.5 h-3.5" /> طالب موثق
                        </span>
                        <span
                            :class="[
                                'rounded-full px-3 py-1 text-xs font-bold',
                                customer.user.is_active
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                    : 'bg-stone-200 text-stone-600 dark:bg-stone-800 dark:text-stone-300',
                            ]"
                        >
                            {{ customer.user.is_active ? 'نشط' : 'موقوف' }}
                        </span>
                        <span
                            v-if="customer.student_status === 'PENDING'"
                            class="flex items-center gap-1 px-3 py-1 bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 rounded-full text-xs font-bold animate-pulse"
                        >
                            <GraduationCap class="w-3.5 h-3.5" /> بانتظار مراجعة الكارنيه
                        </span>
                    </div>
                    <p class="text-stone-500 dark:text-stone-400 text-xs mt-1 font-mono">
                        {{ customer.user.email }} | {{ customer.user.phone || 'بدون هاتف' }}
                    </p>
                </div>
            </div>

            <div v-if="$can('customers.manage')" class="flex items-center gap-2">
                <button
                    type="button"
                    :class="[
                        'px-4 py-2 rounded-xl text-white text-xs font-bold shadow transition',
                        customer.user.is_active ? 'bg-stone-700 hover:bg-stone-800' : 'bg-emerald-600 hover:bg-emerald-700',
                    ]"
                    @click="handleToggleActive"
                >
                    {{ customer.user.is_active ? 'إيقاف الحساب' : 'تفعيل الحساب' }}
                </button>
                <button
                    v-if="customer.user.is_active"
                    type="button"
                    class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow transition flex items-center gap-1.5"
                    @click="confirmLoginAs = true"
                >
                    <LogIn class="w-4 h-4" />
                    <span>تسجيل الدخول كـ هذا العميل</span>
                </button>
                <button
                    v-if="customer.student_status !== 'APPROVED'"
                    type="button"
                    class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow transition flex items-center gap-1.5"
                    @click="handleVerify"
                >
                    <CheckCircle2 class="w-4 h-4" />
                    <span>قبول وتوثيق هوية الطالب</span>
                </button>
                <button
                    v-if="customer.student_status === 'PENDING'"
                    type="button"
                    class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow transition flex items-center gap-1.5"
                    @click="handleReject"
                >
                    <XCircle class="w-4 h-4" />
                    <span>رفض الطلب</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-3xl p-6 shadow-xs space-y-4">
                    <h2 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-2">
                        <GraduationCap class="w-5 h-5 text-orange-500" />
                        <span>بيانات وتوثيق الكارنيه الجامعي</span>
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="p-3.5 rounded-2xl bg-stone-50 dark:bg-stone-800/60 border border-stone-200/80 dark:border-stone-700">
                            <span class="text-stone-400 block mb-1">الجامعة المسجلة:</span>
                            <span class="font-bold text-stone-900 dark:text-white text-sm">
                                {{ customer.university_name || 'غير محدد' }}
                            </span>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-stone-50 dark:bg-stone-800/60 border border-stone-200/80 dark:border-stone-700">
                            <span class="text-stone-400 block mb-1">حالة التوثيق:</span>
                            <span class="font-bold text-stone-900 dark:text-white text-sm">
                                {{ studentStatusLabel }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="cardImageSrc"
                        class="p-4 rounded-2xl bg-stone-50 dark:bg-stone-800/50 border border-stone-200 dark:border-stone-700 space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-stone-700 dark:text-stone-300">صورة كارنيه الطالب المرفوعة:</span>
                            <a
                                :href="cardImageSrc"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-xs font-bold text-orange-600 hover:underline"
                            >
                                فتح الصورة بالحجم الكامل ↗
                            </a>
                        </div>
                        <div class="max-w-md mx-auto rounded-2xl overflow-hidden border border-stone-200 dark:border-stone-700 bg-black/5">
                            <img :src="cardImageSrc" alt="كارنيه الطالب" class="w-full max-h-72 object-contain">
                        </div>
                    </div>
                    <p
                        v-else
                        class="text-xs text-stone-400 p-4 rounded-2xl bg-stone-50 dark:bg-stone-800/40 text-center"
                    >
                        لم يقم العميل برفع صورة الكارنيه بعد.
                    </p>
                </div>

                <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-3xl p-6 shadow-xs">
                    <h2 class="text-base font-black text-stone-900 dark:text-white mb-4">
                        سجل أحدث الطلبات ({{ orders.length }})
                    </h2>
                    <p v-if="orders.length === 0" class="text-stone-400 text-xs text-center py-6">
                        لا توجد طلبات سابقة لهذا العميل
                    </p>
                    <div v-else class="space-y-2.5">
                        <Link
                            v-for="order in orders"
                            :key="order.id"
                            :href="`/admin/orders/${order.id}`"
                            class="flex items-center justify-between p-3.5 rounded-2xl bg-stone-50 dark:bg-stone-800/50 hover:bg-stone-100 dark:hover:bg-stone-800 border border-stone-200/60 dark:border-stone-700/60 transition-colors text-xs"
                        >
                            <div>
                                <p class="font-mono font-bold text-stone-900 dark:text-white">{{ order.order_number }}</p>
                                <p class="text-stone-500 dark:text-stone-400 text-[11px] mt-0.5">
                                    {{ order.restaurant?.name || 'مطعم' }}
                                </p>
                            </div>
                            <div class="text-left">
                                <p class="font-black text-orange-600 dark:text-orange-400">
                                    {{ Number(order.total_amount).toFixed(2) }} ج.م
                                </p>
                                <p class="text-stone-400 text-[10px]">{{ formatDate(order.created_at) }}</p>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-3xl p-6 shadow-xs space-y-4">
                    <h3 class="text-sm font-black text-stone-900 dark:text-white pb-2 border-b border-stone-100 dark:border-stone-800">
                        معلومات الحساب
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <User class="w-4 h-4 text-stone-400 mt-0.5 shrink-0" />
                            <div>
                                <p class="text-[11px] text-stone-400 font-medium">الاسم</p>
                                <p class="text-xs font-bold text-stone-800 dark:text-stone-200 mt-0.5">{{ customer.user.name }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <Phone class="w-4 h-4 text-stone-400 mt-0.5 shrink-0" />
                            <div>
                                <p class="text-[11px] text-stone-400 font-medium">الهاتف</p>
                                <p class="text-xs font-bold text-stone-800 dark:text-stone-200 mt-0.5">{{ customer.user.phone ?? '—' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <Calendar class="w-4 h-4 text-stone-400 mt-0.5 shrink-0" />
                            <div>
                                <p class="text-[11px] text-stone-400 font-medium">تاريخ الانضمام</p>
                                <p class="text-xs font-bold text-stone-800 dark:text-stone-200 mt-0.5">
                                    {{ formatDate(customer.user.created_at) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="customer.addresses && customer.addresses.length > 0"
                    class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-3xl p-6 shadow-xs space-y-3"
                >
                    <h3 class="text-sm font-black text-stone-900 dark:text-white flex items-center gap-2 pb-2 border-b border-stone-100 dark:border-stone-800">
                        <MapPin class="w-4 h-4 text-orange-500" /> العناوين المسجلة
                    </h3>
                    <div class="space-y-2">
                        <div
                            v-for="addr in customer.addresses"
                            :key="addr.id"
                            class="p-3 rounded-xl bg-stone-50 dark:bg-stone-800/60 text-xs border border-stone-200/60 dark:border-stone-700/60"
                        >
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-stone-900 dark:text-white">{{ addr.label }}</span>
                                <span
                                    v-if="addr.is_default"
                                    class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-1.5 py-0.5 rounded"
                                >
                                    افتراضي
                                </span>
                            </div>
                            <p class="text-stone-500 text-[11px] mt-1">{{ addr.address }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ConfirmModal
        :is-open="confirmLoginAs"
        title="تسجيل الدخول كعميل"
        :message="`هل تريد فتح حساب «${customer.user.name}»؟ تقدر ترجع للإدارة من الشريط أعلى الصفحة.`"
        confirm-text="تسجيل الدخول"
        variant="warning"
        @confirm="handleLoginAs"
        @cancel="confirmLoginAs = false"
    />
</template>
