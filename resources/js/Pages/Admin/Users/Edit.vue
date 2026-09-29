<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, User, Shield } from '@lucide/vue';

interface UserData {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    role: string;
    is_active: boolean;
}

const props = defineProps<{
    user: UserData;
    roles: string[];
}>();

const roleLabels: Record<string, string> = {
    SUPER_ADMIN: 'مدير عام',
    ADMIN: 'مدير',
    PLATFORM_STAFF: 'موظف منصة',
    RESTAURANT_OWNER: 'مالك مطعم',
    RESTAURANT_STAFF: 'موظف مطعم',
    DELIVERY_DRIVER: 'سائق توصيل',
    CUSTOMER: 'عميل',
};

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone ?? '',
    password: '',
    role: props.user.role,
    is_active: props.user.is_active,
});

const submit = (): void => {
    form.put(`/admin/users/${props.user.id}`);
};

const normalizeRole = (roleItem: string | { name?: string }): string => {
    if (typeof roleItem === 'string') {
        return roleItem;
    }
    return roleItem?.name ?? String(roleItem);
};
</script>

<template>
    <Head :title="`تعديل ${user.name}`" />

    <div class="max-w-2xl" dir="rtl">
        <div class="flex items-center gap-4 mb-6">
            <Link
                href="/admin/users"
                class="p-2 rounded-lg bg-white hover:bg-stone-100 text-stone-400 hover:text-stone-900 transition-colors"
            >
                <ArrowLeft class="w-5 h-5" />
            </Link>
            <div>
                <h1 class="text-2xl font-bold text-stone-900">تعديل المستخدم</h1>
                <p class="text-stone-400 text-sm mt-1">{{ user.name }}</p>
            </div>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-4 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 flex items-center gap-2">
                    <User class="w-5 h-5 text-orange-400" />
                    البيانات الأساسية
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm text-stone-400 mb-1">الاسم الكامل *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                        <p v-if="form.errors.name" class="text-red-400 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">البريد الإلكتروني *</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                    </div>
                    <div>
                        <label class="block text-sm text-stone-400 mb-1">رقم الهاتف</label>
                        <input
                            v-model="form.phone"
                            type="text"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm text-stone-400 mb-1">كلمة المرور الجديدة (اتركها فارغة إن لم تريد تغييرها)</label>
                        <input
                            v-model="form.password"
                            type="password"
                            placeholder="••••••••"
                            class="w-full bg-white border border-stone-200 rounded-lg px-4 py-2.5 text-stone-900 placeholder-stone-400 focus:outline-none focus:border-orange-500 transition-colors"
                        />
                    </div>
                    <div class="col-span-2 flex items-center gap-3">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="w-4 h-4 accent-orange-500"
                        />
                        <label for="is_active" class="text-sm text-stone-300">الحساب نشط</label>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                <h2 class="text-lg font-semibold text-stone-900 mb-4 flex items-center gap-2">
                    <Shield class="w-5 h-5 text-indigo-400" />
                    الدور
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <label
                        v-for="roleItem in roles || []"
                        :key="normalizeRole(roleItem)"
                        :class="[
                            'flex items-center gap-2 p-3 rounded-lg border cursor-pointer transition-all',
                            form.role === normalizeRole(roleItem)
                                ? 'border-orange-500 bg-orange-500/10'
                                : 'border-stone-200 bg-white hover:border-stone-300',
                        ]"
                    >
                        <input
                            v-model="form.role"
                            type="radio"
                            name="role"
                            :value="normalizeRole(roleItem)"
                            class="accent-orange-500"
                        />
                        <span class="text-sm text-stone-900">{{ roleLabels[normalizeRole(roleItem)] ?? normalizeRole(roleItem) }}</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-4">
                <Link
                    href="/admin/users"
                    class="px-6 py-2.5 bg-white hover:bg-stone-100 text-stone-600 rounded-lg font-medium border border-stone-200 transition-colors"
                >
                    إلغاء
                </Link>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex items-center gap-2 px-6 py-2.5 bg-orange-500 hover:bg-orange-400 text-white rounded-lg font-medium transition-colors disabled:opacity-50"
                >
                    <Save class="w-4 h-4" />
                    {{ form.processing ? 'جاري الحفظ...' : 'حفظ التغييرات' }}
                </button>
            </div>
        </form>
    </div>
</template>
