<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Plus,
    Edit,
    Trash2,
    Users,
    Shield,
    Search,
    Mail,
    Phone,
} from '@lucide/vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';

interface UserItem {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    role: string;
    is_active: boolean;
    created_at: string;
}

const props = withDefaults(
    defineProps<{
        users: {
            data: UserItem[];
            total: number;
            current_page: number;
            last_page: number;
            links?: Array<{ url: string | null; label: string; active: boolean }>;
        };
        roles?: Array<string | { id?: number; name?: string }>;
        filters?: { role?: string; search?: string };
    }>(),
    {
        roles: () => [],
        filters: () => ({}),
    },
);

const roleColors: Record<string, string> = {
    SUPER_ADMIN: 'bg-red-100 text-red-700 dark:bg-red-950/70 dark:text-red-300 border-red-300 dark:border-red-800',
    ADMIN: 'bg-orange-100 text-orange-700 dark:bg-orange-950/70 dark:text-orange-300 border-orange-300 dark:border-orange-800',
    PLATFORM_STAFF: 'bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 border-amber-300 dark:border-amber-800',
    RESTAURANT_OWNER: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300 border-indigo-300 dark:border-indigo-800',
    RESTAURANT_STAFF: 'bg-blue-100 text-blue-700 dark:bg-blue-950/70 dark:text-blue-300 border-blue-300 dark:border-blue-800',
    DELIVERY_DRIVER: 'bg-purple-100 text-purple-700 dark:bg-purple-950/70 dark:text-purple-300 border-purple-300 dark:border-purple-800',
    CUSTOMER: 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-300 border-stone-300 dark:border-stone-700',
};

const roleLabels: Record<string, string> = {
    SUPER_ADMIN: 'مدير عام',
    ADMIN: 'مدير نظام',
    PLATFORM_STAFF: 'موظف منصة',
    RESTAURANT_OWNER: 'مالك مطعم',
    RESTAURANT_STAFF: 'موظف مطعم',
    DELIVERY_DRIVER: 'كابتن توصيل',
    CUSTOMER: 'عميل طالب',
};

const search = ref(props.filters?.search ?? '');
const roleFilter = ref(props.filters?.role ?? '');
const confirmDeleteId = ref<number | null>(null);

const normalizedRoles = computed(() =>
    (props.roles || []).map((r) => {
        if (typeof r === 'string') {
            return r;
        }
        return r?.name ? String(r.name) : String(r);
    }),
);

const handleSearch = (): void => {
    router.get(
        '/admin/users',
        {
            search: search.value || undefined,
            role: roleFilter.value || undefined,
        },
        { preserveState: true },
    );
};

const onRoleChange = (): void => {
    router.get(
        '/admin/users',
        {
            search: search.value || undefined,
            role: roleFilter.value || undefined,
        },
        { preserveState: true },
    );
};

const handleToggleActive = (id: number): void => {
    router.post(`/admin/users/${id}/toggle-active`, {}, { preserveScroll: true });
};

const handleDelete = (id: number): void => {
    confirmDeleteId.value = id;
};

const confirmDelete = (): void => {
    if (confirmDeleteId.value) {
        router.delete(`/admin/users/${confirmDeleteId.value}`, {
            preserveScroll: true,
            onFinish: () => {
                confirmDeleteId.value = null;
            },
        });
    }
};

const formatDate = (date: string): string =>
    new Date(date).toLocaleDateString('ar-EG', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });

const roleClass = (role: string): string =>
    roleColors[role] || 'bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300';

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
    <Head title="إدارة المستخدمين — الإدارة المركزية" />

    <div class="space-y-6 pb-12" dir="rtl">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="w-2 h-2 rounded-full bg-orange-500" />
                    <span class="text-xs font-bold text-orange-600 dark:text-orange-400">لوحة التحكم الإدارية</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-stone-900 dark:text-white">
                    المستخدمون والصلاحيات
                </h1>
                <p class="text-stone-500 dark:text-stone-400 text-xs sm:text-sm mt-0.5">
                    إدارة حسابات المديرين، موظفي المنصة، وأصحاب الصلاحيات ({{ users?.total || 0 }} مسجل)
                </p>
            </div>

            <Link
                href="/admin/users/create"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-700 hover:to-amber-600 text-white rounded-2xl font-black text-xs shadow-md shadow-orange-600/20 transition self-start sm:self-auto"
            >
                <Plus class="w-4 h-4" />
                <span>إضافة مستخدم جديد</span>
            </Link>
        </div>

        <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-3xl p-5 shadow-xs">
            <form class="flex flex-col sm:flex-row items-center gap-3" @submit.prevent="handleSearch">
                <div class="relative flex-1 w-full">
                    <Search class="w-4 h-4 text-stone-400 absolute right-3.5 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="بحث بالاسم أو البريد الإلكتروني أو الهاتف..."
                        class="w-full bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-2xl pr-10 pl-4 py-2.5 text-stone-900 dark:text-white placeholder-stone-400 focus:outline-hidden focus:ring-2 focus:ring-orange-500 transition-colors text-xs"
                    >
                </div>

                <select
                    v-model="roleFilter"
                    class="w-full sm:w-56 bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-2xl px-4 py-2.5 text-stone-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-orange-500 transition-colors text-xs font-bold"
                    @change="onRoleChange"
                >
                    <option value="">جميع الأدوار والصلاحيات</option>
                    <option v-for="r in normalizedRoles" :key="r" :value="r">
                        {{ roleLabels[r] ?? r }}
                    </option>
                </select>

                <button
                    type="submit"
                    class="w-full sm:w-auto px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white rounded-2xl text-xs font-bold transition shadow-xs"
                >
                    بحث
                </button>
            </form>
        </div>

        <div class="bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 rounded-3xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-right">
                    <thead class="bg-stone-50 dark:bg-stone-800/60 border-b border-stone-200 dark:border-stone-800 text-stone-400 font-bold">
                        <tr>
                            <th class="px-6 py-4">المستخدم</th>
                            <th class="px-6 py-4">الدور / الصلاحية</th>
                            <th class="px-6 py-4">الحالة</th>
                            <th class="px-6 py-4">تاريخ الإنشاء</th>
                            <th class="px-6 py-4 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        <tr
                            v-for="user in users?.data || []"
                            :key="user.id"
                            class="hover:bg-stone-50/70 dark:hover:bg-stone-800/40 transition"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-orange-100 dark:bg-stone-800 text-orange-600 dark:text-orange-400 flex items-center justify-center font-black text-sm">
                                        {{ user.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <span class="font-bold text-stone-900 dark:text-white text-sm block">
                                            {{ user.name }}
                                        </span>
                                        <div class="flex items-center gap-2 text-stone-400 text-[11px] mt-0.5 font-mono">
                                            <Mail class="w-3 h-3" />
                                            <span>{{ user.email }}</span>
                                        </div>
                                        <div
                                            v-if="user.phone"
                                            class="flex items-center gap-2 text-stone-400 text-[11px] font-mono"
                                        >
                                            <Phone class="w-3 h-3" />
                                            <span>{{ user.phone }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black border',
                                        roleClass(user.role),
                                    ]"
                                >
                                    <Shield class="w-3 h-3" />
                                    <span>{{ roleLabels[user.role] || user.role }}</span>
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <button
                                    type="button"
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold border transition',
                                        user.is_active
                                            ? 'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-300 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300'
                                            : 'bg-stone-100 dark:bg-stone-800 border-stone-300 dark:border-stone-700 text-stone-500',
                                    ]"
                                    title="انقر لتفعيل أو تعطيل الحساب"
                                    @click="handleToggleActive(user.id)"
                                >
                                    <span
                                        :class="[
                                            'w-1.5 h-1.5 rounded-full',
                                            user.is_active ? 'bg-emerald-500' : 'bg-stone-400',
                                        ]"
                                    />
                                    <span>{{ user.is_active ? 'نشط' : 'معطل' }}</span>
                                </button>
                            </td>

                            <td class="px-6 py-4 text-stone-400 text-[11px]">
                                {{ formatDate(user.created_at) }}
                            </td>

                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <Link
                                        :href="`/admin/users/${user.id}/edit`"
                                        class="p-2 rounded-xl text-stone-500 hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-stone-800 transition"
                                        title="تعديل المستخدم"
                                    >
                                        <Edit class="w-4 h-4" />
                                    </Link>
                                    <button
                                        type="button"
                                        class="p-2 rounded-xl text-stone-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-stone-800 transition"
                                        title="حذف المستخدم"
                                        @click="handleDelete(user.id)"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div v-if="!users?.data || users.data.length === 0" class="text-center py-16">
                    <Users class="w-12 h-12 text-stone-300 dark:text-stone-600 mx-auto mb-2" />
                    <p class="text-xs text-stone-400">لا يوجد مستخدمون مسجلون مطابقون للبحث.</p>
                </div>
            </div>
        </div>

        <div
            v-if="users?.links && users.links.length > 3"
            class="pt-4 flex items-center justify-center gap-1.5"
        >
            <Link
                v-for="(link, idx) in users.links"
                :key="idx"
                :href="link.url || '#'"
                preserve-scroll
                :class="['px-3.5 py-2 rounded-xl text-xs font-bold transition', paginationClass(link)]"
                v-html="link.label"
            />
        </div>
    </div>

    <ConfirmModal
        :is-open="confirmDeleteId !== null"
        message="هل تريد بالتأكيد حذف هذا المستخدم؟ لن تتمكن من استعادته."
        @confirm="confirmDelete"
        @cancel="confirmDeleteId = null"
    />
</template>
