<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Activity, ChevronDown, Clock, Globe, Search, User } from '@lucide/vue';

interface LogUser {
    id: number;
    name: string;
    email?: string;
    role?: string;
}

interface Log {
    id: number;
    action: string;
    entity_type?: string | null;
    entity_id?: number | null;
    old_values?: Record<string, unknown> | null;
    new_values?: Record<string, unknown> | null;
    ip_address?: string | null;
    created_at: string;
    user?: LogUser | null;
}

const props = withDefaults(
    defineProps<{
        logs: {
            data: Log[];
            total: number;
            current_page: number;
            last_page: number;
            links?: Array<{ url: string | null; label: string; active: boolean }>;
        };
        actions?: string[];
        filters?: {
            search?: string;
            action?: string;
            date_from?: string;
            date_to?: string;
        };
    }>(),
    {
        actions: () => [],
        filters: () => ({}),
    },
);

const actionLabels: Record<string, string> = {
    USER_LOGIN: 'تسجيل دخول',
    USER_LOGOUT: 'تسجيل خروج',
    FAILED_LOGIN_ATTEMPT: 'محاولة دخول فاشلة',
    UNAUTHORIZED_PORTAL_ACCESS_ATTEMPT: 'محاولة دخول لبوابة غير مصرح بها',
    USER_CREATED: 'إنشاء مستخدم',
    USER_UPDATED: 'تعديل مستخدم',
    USER_DELETED: 'حذف مستخدم',
    USER_TOGGLE_ACTIVE: 'تغيير حالة مستخدم',
    ROLE_PERMISSIONS_UPDATED: 'تحديث صلاحيات دور',
    ORDER_CREATED: 'إنشاء طلب',
    ORDER_STATUS_CHANGED: 'تغيير حالة طلب',
    DRIVER_ASSIGNED: 'تعيين كابتن توصيل',
    RESTAURANT_CREATED: 'إضافة مطعم',
    RESTAURANT_UPDATED: 'تعديل مطعم',
    RESTAURANT_DELETED: 'حذف مطعم',
    RESTAURANT_SUSPENDED: 'إيقاف مطعم',
    RESTAURANT_ACTIVATED: 'تفعيل مطعم',
    RESTAURANT_OPENED: 'فتح المطعم',
    RESTAURANT_CLOSED: 'إغلاق المطعم',
    RESTAURANT_FINANCIAL_CONFIG_UPDATED: 'تحديث الإعدادات المالية للمطعم',
    RESTAURANT_AVAILABILITY_UPDATED: 'تحديث حالة توفر المطعم',
    RESTAURANT_AUTO_SUSPENDED: 'إيقاف مطعم تلقائيًا',
    RESTAURANT_SUSPENDED_FOR_BILLING: 'إيقاف مطعم بسبب الفواتير',
    INVOICE_CREATED: 'إنشاء فاتورة',
    INVOICE_UPDATED: 'تعديل فاتورة',
    INVOICE_ISSUED: 'إصدار فاتورة',
    INVOICE_MARKED_PAID: 'تسجيل فاتورة كمدفوعة',
    INVOICE_CANCELLED: 'إلغاء فاتورة',
    BILLING_AUTO_GENERATE: 'توليد فواتير تلقائي',
    COLLECTION_RECORDED: 'تسجيل تحصيل',
    STUDENT_VERIFIED: 'توثيق طالب',
    STUDENT_REJECTED: 'رفض توثيق طالب',
    DELIVERY_DRIVER_CREATED: 'إضافة كابتن توصيل',
    DELIVERY_DRIVER_DELETED: 'حذف كابتن توصيل',
    SYSTEM_SETTINGS_UPDATED: 'تحديث إعدادات النظام',
    CMS_SETTINGS_UPDATED: 'تحديث محتوى الصفحة',
    BACKUP_CREATED: 'إنشاء نسخة احتياطية',
    BACKUP_FAILED: 'فشل النسخ الاحتياطي',
    BACKUP_DELETED: 'حذف نسخة احتياطية',
};

const entityLabels: Record<string, string> = {
    User: 'مستخدم',
    Order: 'طلب',
    Restaurant: 'مطعم',
    Invoice: 'فاتورة',
    Collection: 'تحصيل',
    Customer: 'عميل',
    Backup: 'نسخة احتياطية',
    DeliveryDriver: 'كابتن توصيل',
    Role: 'دور',
};

const valueLabels: Record<string, string> = {
    login: 'بيانات الدخول',
    portal: 'البوابة',
    user_role: 'الدور',
    role: 'الدور',
    status: 'الحالة',
    availability_status: 'حالة التوفر',
    notes: 'ملاحظات',
    order_number: 'رقم الطلب',
    total: 'الإجمالي',
    driver_id: 'رقم الكابتن',
    driver_name: 'اسم الكابتن',
    name: 'الاسم',
    number: 'الرقم',
    count: 'العدد',
    filename: 'الملف',
    error: 'الخطأ',
    is_active: 'نشط',
    restaurant_id: 'المطعم',
    amount: 'المبلغ',
};

const search = ref(props.filters?.search ?? '');
const actionFilter = ref(props.filters?.action ?? '');
const dateFrom = ref(props.filters?.date_from ?? '');
const dateTo = ref(props.filters?.date_to ?? '');
const expandedId = ref<number | null>(null);

const actionOptions = computed(() => [...(props.actions ?? [])].sort());

const applyFilters = (): void => {
    router.get(
        '/admin/activity-logs',
        {
            search: search.value || undefined,
            action: actionFilter.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
};

const clearFilters = (): void => {
    search.value = '';
    actionFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    applyFilters();
};

const hasFilters = computed(
    () => Boolean(search.value || actionFilter.value || dateFrom.value || dateTo.value),
);

const actionLabel = (action: string): string => actionLabels[action] ?? action;

const actionClass = (action: string): string => {
    if (action.includes('FAIL') || action.includes('UNAUTHORIZED') || action.includes('DELETED') || action.includes('REJECT') || action.includes('SUSPEND') || action.includes('CANCEL')) {
        return 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/60 dark:text-red-300 dark:border-red-900';
    }

    if (action.startsWith('ORDER') || action.startsWith('DRIVER')) {
        return 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/60 dark:text-orange-300 dark:border-orange-900';
    }

    if (action.startsWith('INVOICE') || action.startsWith('COLLECTION') || action.startsWith('BILLING')) {
        return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-900';
    }

    if (action.includes('LOGIN') || action.includes('LOGOUT')) {
        return 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-900';
    }

    return 'bg-stone-100 text-stone-700 border-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:border-stone-700';
};

const entityLabel = (type?: string | null): string => {
    if (!type) {
        return '';
    }

    return entityLabels[type] ?? type;
};

const formatDate = (value: string): string =>
    new Date(value).toLocaleString('ar-EG', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });

const formatValue = (value: unknown): string => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'boolean') {
        return value ? 'نعم' : 'لا';
    }

    if (typeof value === 'object') {
        return JSON.stringify(value);
    }

    return String(value);
};

const valueRows = (values?: Record<string, unknown> | null): Array<{ key: string; label: string; value: string }> =>
    Object.entries(values ?? {}).map(([key, value]) => ({
        key,
        label: valueLabels[key] ?? key,
        value: formatValue(value),
    }));

const hasDetails = (log: Log): boolean =>
    valueRows(log.old_values).length > 0 || valueRows(log.new_values).length > 0 || Boolean(log.ip_address);

const toggleDetails = (id: number): void => {
    expandedId.value = expandedId.value === id ? null : id;
};

const paginationClass = (link: { url: string | null; active: boolean }): string => {
    if (link.active) {
        return 'bg-orange-600 text-white shadow-xs';
    }

    if (!link.url) {
        return 'text-stone-300 dark:text-stone-600 cursor-not-allowed';
    }

    return 'text-stone-600 dark:text-stone-400 hover:bg-stone-100 dark:hover:bg-stone-800 bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800';
};
</script>

<template>
    <Head title="سجل النشاطات" />

    <div class="space-y-6 pb-12" dir="rtl">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-orange-500" />
                <span class="text-xs font-bold text-orange-600 dark:text-orange-400">لوحة التحكم الإدارية</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-stone-900 dark:text-white flex items-center gap-2">
                <Activity class="w-7 h-7 text-orange-500" />
                سجل النشاطات
            </h1>
            <p class="text-stone-500 dark:text-stone-400 text-xs sm:text-sm mt-0.5">
                {{ logs.total }} نشاط مسجل على المنصة
            </p>
        </div>

        <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-3xl p-5 shadow-xs">
            <form class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3 items-end" @submit.prevent="applyFilters">
                <div class="relative xl:col-span-2">
                    <Search class="w-4 h-4 text-stone-400 absolute right-3.5 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="بحث بالإجراء أو المستخدم أو نوع السجل..."
                        class="w-full bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-2xl pr-10 pl-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 focus:outline-hidden focus:ring-2 focus:ring-orange-500 transition-colors text-xs"
                    >
                </div>

                <select
                    v-model="actionFilter"
                    class="w-full bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-2xl px-4 py-2.5 text-stone-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-orange-500 transition-colors text-xs font-bold"
                    @change="applyFilters"
                >
                    <option value="">كل الإجراءات</option>
                    <option v-for="action in actionOptions" :key="action" :value="action">
                        {{ actionLabel(action) }}
                    </option>
                </select>

                <label class="block space-y-1">
                    <span class="text-[11px] font-bold text-stone-400 px-1">من تاريخ</span>
                    <input
                        v-model="dateFrom"
                        type="date"
                        class="w-full bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-2xl px-4 py-2.5 text-stone-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-orange-500 transition-colors text-xs"
                        @change="applyFilters"
                    >
                </label>

                <label class="block space-y-1">
                    <span class="text-[11px] font-bold text-stone-400 px-1">إلى تاريخ</span>
                    <input
                        v-model="dateTo"
                        type="date"
                        class="w-full bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-2xl px-4 py-2.5 text-stone-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-orange-500 transition-colors text-xs"
                        @change="applyFilters"
                    >
                </label>

                <div class="md:col-span-2 xl:col-span-5 flex items-center gap-2">
                    <button
                        type="submit"
                        class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white rounded-2xl text-xs font-bold transition shadow-xs"
                    >
                        بحث
                    </button>
                    <button
                        v-if="hasFilters"
                        type="button"
                        class="px-5 py-2.5 bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300 rounded-2xl text-xs font-bold transition"
                        @click="clearFilters"
                    >
                        مسح الفلاتر
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-3xl overflow-hidden shadow-xs">
            <div class="divide-y divide-stone-100 dark:divide-stone-800">
                <div v-for="log in logs.data" :key="log.id" class="px-5 sm:px-6 py-4">
                    <div class="flex items-start gap-3 sm:gap-4">
                        <div :class="['mt-0.5 p-2 rounded-xl border shrink-0', actionClass(log.action)]">
                            <Activity class="w-4 h-4" />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-stone-900 dark:text-white text-sm font-bold">
                                    {{ actionLabel(log.action) }}
                                </p>
                                <span
                                    v-if="log.entity_type"
                                    class="text-[11px] font-bold text-stone-500 dark:text-stone-400"
                                >
                                    {{ entityLabel(log.entity_type) }}
                                    <span v-if="log.entity_id">#{{ log.entity_id }}</span>
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-1.5">
                                <span class="flex items-center gap-1 text-xs text-stone-500 dark:text-stone-400">
                                    <User class="w-3 h-3" />
                                    {{ log.user?.name ?? 'النظام' }}
                                </span>
                                <span class="flex items-center gap-1 text-xs text-stone-500 dark:text-stone-400">
                                    <Clock class="w-3 h-3" />
                                    {{ formatDate(log.created_at) }}
                                </span>
                                <span
                                    v-if="log.ip_address"
                                    class="flex items-center gap-1 text-xs text-stone-400 font-mono"
                                >
                                    <Globe class="w-3 h-3" />
                                    {{ log.ip_address }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button
                                v-if="hasDetails(log)"
                                type="button"
                                class="p-2 rounded-xl text-stone-400 hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-stone-800 transition"
                                aria-label="عرض التفاصيل"
                                :aria-expanded="expandedId === log.id"
                                @click="toggleDetails(log.id)"
                            >
                                <ChevronDown
                                    :class="['w-4 h-4 transition-transform', expandedId === log.id ? 'rotate-180' : '']"
                                />
                            </button>
                        </div>
                    </div>

                    <div
                        v-if="expandedId === log.id"
                        class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3"
                    >
                        <div
                            v-if="valueRows(log.old_values).length"
                            class="rounded-2xl bg-stone-50 dark:bg-stone-800/70 border border-stone-200 dark:border-stone-700 p-4"
                        >
                            <p class="text-[11px] font-black text-stone-400 mb-2">القيم السابقة</p>
                            <dl class="space-y-1.5">
                                <div
                                    v-for="row in valueRows(log.old_values)"
                                    :key="`old-${log.id}-${row.key}`"
                                    class="flex items-start justify-between gap-3 text-xs"
                                >
                                    <dt class="text-stone-500">{{ row.label }}</dt>
                                    <dd class="text-stone-900 dark:text-white font-bold text-left break-all">{{ row.value }}</dd>
                                </div>
                            </dl>
                        </div>

                        <div
                            v-if="valueRows(log.new_values).length"
                            class="rounded-2xl bg-stone-50 dark:bg-stone-800/70 border border-stone-200 dark:border-stone-700 p-4"
                        >
                            <p class="text-[11px] font-black text-stone-400 mb-2">القيم الجديدة</p>
                            <dl class="space-y-1.5">
                                <div
                                    v-for="row in valueRows(log.new_values)"
                                    :key="`new-${log.id}-${row.key}`"
                                    class="flex items-start justify-between gap-3 text-xs"
                                >
                                    <dt class="text-stone-500">{{ row.label }}</dt>
                                    <dd class="text-stone-900 dark:text-white font-bold text-left break-all">{{ row.value }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <div v-if="logs.data.length === 0" class="py-16 text-center">
                    <Activity class="w-12 h-12 text-stone-300 dark:text-stone-600 mx-auto mb-3" />
                    <p class="text-sm text-stone-400">لا توجد نشاطات مطابقة للبحث</p>
                </div>
            </div>
        </div>

        <div
            v-if="logs.links && logs.links.length > 3"
            class="flex items-center justify-center gap-1.5"
        >
            <Link
                v-for="(link, idx) in logs.links"
                :key="idx"
                :href="link.url || '#'"
                preserve-scroll
                :class="['px-3.5 py-2 rounded-xl text-xs font-bold transition', paginationClass(link)]"
                v-html="link.label"
            />
        </div>
    </div>
</template>
