<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Customer, PaginatedResponse } from '../../../Types';
import { Search } from '@lucide/vue';

const props = defineProps<{
    customers: PaginatedResponse<Customer & { orders_count: number }>;
    filters: { search?: string; student_status?: string };
}>();

const items = computed(() => props.customers?.data || []);
const search = ref(props.filters.search || '');
const previewImage = ref<string | null>(null);

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

const openPreview = (img: string): void => {
    previewImage.value = img.startsWith('http') || img.startsWith('/') ? img : `/storage/${img}`;
};

const isStatusActive = (key: string): boolean =>
    props.filters.student_status === key || (!props.filters.student_status && key === 'ALL');
</script>

<template>
    <Head title="إدارة الطلاب والعملاء — الإدارة المركزية" />

    <div class="space-y-6">
        <div class="bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-stone-100 dark:border-stone-800">
                <div>
                    <h1 class="text-xl font-black text-stone-900 dark:text-white">
                        الطلاب والعملاء المسجلين
                    </h1>
                    <p class="text-xs text-stone-400 mt-0.5">
                        مراجعة وتوثيق بطاقات وكارنيهات طلاب جامعات برج العرب لتفعيل الخصم
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
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="border-b border-stone-200 dark:border-stone-800 text-stone-400 text-[11px] font-bold">
                            <th class="py-3 px-4">اسم الطالب / العميل</th>
                            <th class="py-3 px-4">رقم الهاتف والبريد</th>
                            <th class="py-3 px-4">الجامعة</th>
                            <th class="py-3 px-4">حالة التوثيق</th>
                            <th class="py-3 px-4">عدد الطلبات</th>
                            <th class="py-3 px-4 text-center">إجراءات المراجعة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        <tr
                            v-for="c in items"
                            :key="c.id"
                            class="hover:bg-stone-50 dark:hover:bg-stone-800/50"
                        >
                            <td class="py-4 px-4 font-bold text-stone-900 dark:text-white">
                                {{ c.user?.name }}
                            </td>
                            <td class="py-4 px-4 text-stone-600 dark:text-stone-400">
                                <span class="font-mono block">{{ c.user?.phone || '—' }}</span>
                                <span class="text-[11px] text-stone-400">{{ c.user?.email }}</span>
                            </td>
                            <td class="py-4 px-4 text-stone-700 dark:text-stone-300 font-medium">
                                {{ c.university_name || '—' }}
                            </td>
                            <td class="py-4 px-4">
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
                            <td class="py-4 px-4 font-bold text-stone-900 dark:text-white">
                                {{ c.orders_count || 0 }} طلب
                            </td>
                            <td class="py-4 px-4 text-center space-x-1.5 space-x-reverse">
                                <button
                                    v-if="c.university_id_card_image"
                                    type="button"
                                    class="px-2.5 py-1 rounded-lg bg-orange-100 dark:bg-orange-950/60 text-orange-700 dark:text-orange-300 font-bold text-[11px] hover:bg-orange-200 transition"
                                    @click="openPreview(c.university_id_card_image!)"
                                >
                                    معاينة الكارنيه 🎓
                                </button>

                                <button
                                    v-if="c.student_status !== 'APPROVED' && $can('customers.manage')"
                                    type="button"
                                    class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px]"
                                    @click="handleVerify(c.id)"
                                >
                                    قبول وتوثيق
                                </button>

                                <button
                                    v-if="c.student_status === 'PENDING' && $can('customers.manage')"
                                    type="button"
                                    class="px-2.5 py-1 rounded-lg bg-red-600 hover:bg-red-700 text-white font-bold text-[11px]"
                                    @click="handleReject(c.id)"
                                >
                                    رفض
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
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
                class="mt-4 px-6 py-2 rounded-xl bg-stone-900 dark:bg-white text-white dark:text-white text-xs font-bold"
                @click="previewImage = null"
            >
                إغلاق
            </button>
        </div>
    </div>
</template>
