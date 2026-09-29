<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Shield, Users, Check } from '@lucide/vue';

interface Role {
    name: string;
    users_count: number;
    permissions: string[];
}

defineProps<{
    roles: Role[];
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

const roleColors: Record<string, string> = {
    SUPER_ADMIN: 'from-red-500/20 to-red-600/10 border-red-500/30',
    ADMIN: 'from-orange-500/20 to-orange-600/10 border-orange-500/30',
    PLATFORM_STAFF: 'from-amber-500/20 to-amber-600/10 border-amber-500/30',
    RESTAURANT_OWNER: 'from-indigo-500/20 to-indigo-600/10 border-indigo-500/30',
    RESTAURANT_STAFF: 'from-blue-500/20 to-blue-600/10 border-blue-500/30',
    DELIVERY_DRIVER: 'from-purple-500/20 to-purple-600/10 border-purple-500/30',
    CUSTOMER: 'from-stone-500/20 to-stone-600/10 border-stone-500/30',
};

const permLabels: Record<string, string> = {
    'restaurants.view': 'عرض المطاعم',
    'restaurants.create': 'إنشاء المطاعم',
    'restaurants.update': 'تعديل المطاعم',
    'restaurants.delete': 'حذف المطاعم',
    'orders.view': 'عرض الطلبات',
    'orders.manage': 'إدارة الطلبات',
    'finance.view': 'عرض المالية',
    'finance.manage': 'إدارة المالية',
    'users.view': 'عرض المستخدمين',
    'users.manage': 'إدارة المستخدمين',
    'settings.manage': 'إدارة الإعدادات',
};
</script>

<template>
    <Head title="الأدوار والصلاحيات" />

    <div class="space-y-6" dir="rtl">
        <div>
            <h1 class="text-2xl font-bold text-stone-900">الأدوار والصلاحيات</h1>
            <p class="text-stone-400 text-sm mt-1">نظرة عامة على أدوار المستخدمين وصلاحياتهم</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <div
                v-for="role in roles"
                :key="role.name"
                :class="['bg-gradient-to-br border rounded-2xl p-6', roleColors[role.name] ?? roleColors.CUSTOMER]"
            >
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <Shield class="w-5 h-5 text-stone-900/70" />
                            <h2 class="text-lg font-bold text-stone-900">{{ roleLabels[role.name] ?? role.name }}</h2>
                        </div>
                        <p class="text-xs text-stone-400 mt-1 font-mono">{{ role.name }}</p>
                    </div>
                    <div class="flex items-center gap-1 bg-white/10 px-2 py-1 rounded-lg">
                        <Users class="w-3.5 h-3.5 text-stone-300" />
                        <span class="text-sm text-stone-300 font-semibold">{{ role.users_count }}</span>
                    </div>
                </div>

                <div v-if="role.permissions.length > 0" class="space-y-2">
                    <p class="text-xs text-stone-500 uppercase font-medium mb-2">الصلاحيات</p>
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="perm in role.permissions"
                            :key="perm"
                            class="flex items-center gap-1 px-2 py-0.5 bg-white/10 text-stone-300 rounded text-xs"
                        >
                            <Check class="w-2.5 h-2.5 text-emerald-400" />
                            {{ permLabels[perm] ?? perm }}
                        </span>
                    </div>
                </div>
                <p v-else class="text-stone-500 text-xs">صلاحيات محدودة (حسب الدور)</p>
            </div>
        </div>
    </div>
</template>
