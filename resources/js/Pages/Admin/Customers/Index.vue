<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Customer, PaginatedResponse } from '../../../Types';
import { Eye, LogIn, Search } from '@lucide/vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';

const props = defineProps<{
    customers: PaginatedResponse<Customer & { orders_count: number }>;
    filters: { search?: string; student_status?: string };
}>();

const items = computed(() => props.customers?.data || []);
const search = ref(props.filters.search || '');
const previewImage = ref<string | null>(null);
const loginAsId = ref<number | null>(null);
const loginAsName = ref('');

const statusTabs = [
    { key: 'ALL', label: 'الكل' },
    { key: 'PENDING', label: 'بانتظار المراجعة' },
    { key: 'APPROVED', label: 'مفعل (طلاب)' },
    { key: 'REJECTED', label: 'مرفوض' },
];

const handleSearch = (): void => {
    router.get(
        '/admin/customers',
        { search: search.value, student_status: props.filters.student_status },
        { preserveState: true },
    );
};

const handleFilterStatus = (st: string): void => {
    router.get(
        '/admin/customers',
        { search: search.value, student_status: st === 'ALL' ? '' : st },
        { preserveState: true },
    );
};

const handleVerify = (id: number): void => {
    router.patch(`/admin/customers/${id}/verify-student`);
};

const handleReject = (id: number): void => {
    router.patch(`/admin/customers/${id}/reject-student`);
};

const handleToggleActive = (id: number): void => {
    router.post(`/admin/customers/${id}/toggle-active`, {}, { preserveScroll: true });
};

const openPreview = (img: string): void => {
    previewImage.value = img.startsWith('http') || img.startsWith('/') ? img : `/storage/${img}`;
};

const isStatusActive = (key: string): boolean =>
    props.filters.student_status === key || (!props.filters.student_status && key === 'ALL');

const askLoginAs = (customer: Customer): void => {
    loginAsId.value = customer.id;
    loginAsName.value = customer.user?.name || 'هذا العميل';
};

const confirmLoginAs = (): void => {
    if (loginAsId.value === null) {
        return;
    }

    router.post(`/admin/customers/${loginAsId.value}/login-as`);
    loginAsId.value = null;
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
    <Head title="العملاء — الإدارة المركزية" />

    <div class="space-y-6">
        <div class="bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-stone-100 dark:border-stone-800">
                <div>
                    <h1 class="text-xl font-black text-stone-900 dark:text-white">
                        العملاء
                    </h1>
                    <p class="text-xs text-stone-400 mt-0.5">
                        متابعة الحسابات، وتوثيق كارنيه الطالب، والدخول بحساب العميل عند الحاجة
                    </p>
                </div>

                <div class="flex items-center gap-1.5 overflow-x-auto pb-2 sm:pb-0 custom-scrollbar">
                    <button
                        v-for="st in statusTabs"
                        :key="st.key"
                        type="button"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition',
                            isStatusActive(st.key)
                                ? 'bg-blue-600 text-white shadow-xs'
                                : 'bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-400',
                        ]"
                        @click="handleFilterStatus(st.key)"
                    >
                        {{ st.label }}
                    </button>
                </div>
            </div>

            <form class="mb-6 max-w-sm relative" @submit.prevent="handleSearch">
                <Search class="w-4 h-4 text-stone-400 absolute right-3.5 top-3" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="ابحث بالاسم أو الهاتف..."
                    class="w-full pr-10 pl-4 py-2 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white"
                >
            </form>

            <div class="overflow-x-auto">
                <table class="record-cards w-full text-right text-xs">
                    <thead>
                        <tr class="border-b border-stone-200 dark:border-stone-800 text-stone-400 text-[11px] font-bold">
                            <th class="py-3 px-4">العميل</th>
                            <th class="py-3 px-4">رقم الهاتف والبريد</th>
                            <th class="py-3 px-4">الجامعة</th>
                            <th class="py-3 px-4">حالة التوثيق</th>
                            <th class="py-3 px-4">حالة الحساب</th>
                            <th class="py-3 px-4">عدد الطلبات</th>
                            <th class="py-3 px-4 text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        <tr
                            v-for="c in items"
                            :key="c.id"
                            class="hover:bg-stone-50 dark:hover:bg-stone-800/50"
                        >
                            <td data-label="العميل" class="is-title py-4 px-4 font-bold text-stone-900 dark:text-white">
                                <Link :href="`/admin/customers/${c.id}`" class="hover:text-orange-600">
                                    {{ c.user?.name }}
                                </Link>
                            </td>
                            <td data-label="رقم الهاتف والبريد" class="py-4 px-4 text-stone-600 dark:text-stone-400">
                                <span class="font-mono block">{{ c.user?.phone || '—' }}</span>
                                <span class="text-[11px] text-stone-400">{{ c.user?.email }}</span>
                            </td>
                            <td data-label="الجامعة" class="py-4 px-4 text-stone-700 dark:text-stone-300 font-medium">
                                {{ c.university_name || '—' }}
                            </td>
                            <td data-label="حالة التوثيق" class="py-4 px-4">
                                <span
                                    v-if="c.student_status === 'APPROVED'"
                                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300"
                                >
                                    طالب موثق
                                </span>
                                <span
                                    v-else-if="c.student_status === 'PENDING'"
                                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 animate-pulse"
                                >
                                    بانتظار المراجعة
                                </span>
                                <span
                                    v-else-if="c.student_status === 'REJECTED'"
                                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-100 dark:bg-red-950 text-red-700 dark:text-red-300"
                                >
                                    مرفوض
                                </span>
                                <span
                                    v-else
                                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-stone-100 dark:bg-stone-800 text-stone-500"
                                >
                                    عميل عادي
                                </span>
                            </td>
                            <td data-label="حالة الحساب" class="py-4 px-4">
                                <button
                                    v-if="$can('customers.manage')"
                                    type="button"
                                    :class="[
                                        'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-[11px] font-bold transition',
                                        c.user?.is_active
                                            ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300'
                                            : 'border-stone-300 bg-stone-100 text-stone-500 dark:border-stone-700 dark:bg-stone-800',
                                    ]"
                                    title="انقر لتفعيل أو إيقاف الحساب"
                                    @click="handleToggleActive(c.id)"
                                >
                                    <span
                                        :class="['h-1.5 w-1.5 rounded-full', c.user?.is_active ? 'bg-emerald-500' : 'bg-stone-400']"
                                    />
                                    <span>{{ c.user?.is_active ? 'نشط' : 'موقوف' }}</span>
                                </button>
                                <span
                                    v-else
                                    :class="[
                                        'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-[11px] font-bold',
                                        c.user?.is_active
                                            ? 'border-emerald-300 bg-emerald-50 text-emerald-700'
                                            : 'border-stone-300 bg-stone-100 text-stone-500',
                                    ]"
                                >
                                    {{ c.user?.is_active ? 'نشط' : 'موقوف' }}
                                </span>
                            </td>
                            <td data-label="عدد الطلبات" class="py-4 px-4 font-bold text-stone-900 dark:text-white">
                                {{ c.orders_count || 0 }} طلب
                            </td>
                            <td data-label="إجراءات" class="is-actions py-4 px-4">
                                <div class="flex items-center justify-center gap-1">
                                    <Link
                                        :href="`/admin/customers/${c.id}`"
                                        class="rounded-lg p-1.5 text-stone-400 transition hover:bg-orange-50 hover:text-orange-600 dark:hover:bg-orange-950/40"
                                        title="عرض التفاصيل"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </Link>
                                    <button
                                        v-if="$can('customers.manage') && c.user?.is_active"
                                        type="button"
                                        class="rounded-lg p-1.5 text-stone-400 transition hover:bg-sky-50 hover:text-sky-600 dark:hover:bg-sky-950/40"
                                        title="تسجيل الدخول كـ هذا العميل"
                                        @click="askLoginAs(c)"
                                    >
                                        <LogIn class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="c.university_id_card_image"
                                        type="button"
                                        class="rounded-lg px-2.5 py-1 text-[11px] font-bold text-orange-700 transition hover:bg-orange-100 dark:text-orange-300 dark:hover:bg-orange-950/60"
                                        @click="openPreview(c.university_id_card_image!)"
                                    >
                                        الكارنيه
                                    </button>
                                    <button
                                        v-if="c.student_status !== 'APPROVED' && $can('customers.manage')"
                                        type="button"
                                        class="rounded-lg bg-emerald-600 px-2.5 py-1 text-[11px] font-bold text-white hover:bg-emerald-700"
                                        @click="handleVerify(c.id)"
                                    >
                                        توثيق
                                    </button>
                                    <button
                                        v-if="c.student_status === 'PENDING' && $can('customers.manage')"
                                        type="button"
                                        class="rounded-lg bg-red-600 px-2.5 py-1 text-[11px] font-bold text-white hover:bg-red-700"
                                        @click="handleReject(c.id)"
                                    >
                                        رفض
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="items.length === 0">
                            <td colspan="7" class="py-16 text-center text-xs text-stone-400">
                                لا يوجد عملاء مطابقون للبحث.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="customers?.links && customers.links.length > 3"
                class="flex items-center justify-center gap-1.5 pt-4"
            >
                <Link
                    v-for="(link, idx) in customers.links"
                    :key="idx"
                    :href="link.url || '#'"
                    preserve-scroll
                    :class="['rounded-xl px-3.5 py-2 text-xs font-bold transition', paginationClass(link)]"
                    v-html="link.label"
                />
            </div>
        </div>
    </div>

    <div v-if="previewImage" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div
            class="fixed inset-0 bg-stone-950/80 backdrop-blur-sm"
            @click="previewImage = null"
        />
        <div class="relative bg-white dark:bg-stone-900 p-4 rounded-3xl max-w-lg w-full z-10 shadow-2xl animate-fade-in text-center">
            <h3 class="font-bold text-sm mb-3">معاينة صورة كارنيه الطالب</h3>
            <img :src="previewImage" alt="كارنيه الطالب" class="max-h-[60vh] mx-auto rounded-xl object-contain">
            <button
                type="button"
                class="mt-4 px-6 py-2 rounded-xl bg-stone-900 text-xs font-bold text-white dark:bg-white dark:text-stone-900"
        @click="previewImage = null"
    >
        إغلاق
    </button>
        </div>
    </div>

    <ConfirmModal
        :is-open="loginAsId !== null"
        title="تسجيل الدخول كعميل"
        :message="`هل تريد فتح حساب «${loginAsName}»؟ تقدر ترجع للإدارة من الشريط أعلى الصفحة.`"
        confirm-text="تسجيل الدخول"
        variant="warning"
        @confirm="confirmLoginAs"
        @cancel="loginAsId = null"
    />
</template>
