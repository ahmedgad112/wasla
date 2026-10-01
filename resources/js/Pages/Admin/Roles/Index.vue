<script setup lang="ts">
import { reactive, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Shield, Users } from '@lucide/vue';

interface PermissionItem {
    name: string;
    label: string;
}

interface CatalogGroup {
    key: string;
    label: string;
    permissions: PermissionItem[];
}

interface RoleRow {
    name: string;
    label: string;
    users_count: number;
    permissions: string[];
}

const props = defineProps<{
    roles: RoleRow[];
    catalog: CatalogGroup[];
}>();

const drafts = reactive<Record<string, string[]>>({});
const saving = reactive<Record<string, boolean>>({});

function syncDrafts(): void {
    props.roles.forEach((role) => {
        drafts[role.name] = [...role.permissions];
    });
}

watch(() => props.roles, syncDrafts, { immediate: true, deep: true });

function hasPermission(roleName: string, permission: string): boolean {
    return (drafts[roleName] ?? []).includes(permission);
}

function togglePermission(role: RoleRow, permission: string): void {
    const current = drafts[role.name] ?? [];
    drafts[role.name] = current.includes(permission)
        ? current.filter((item) => item !== permission)
        : [...current, permission];
}

function setGroup(role: RoleRow, permissions: PermissionItem[], enabled: boolean): void {
    const names = permissions.map((item) => item.name);
    const current = new Set(drafts[role.name] ?? []);

    names.forEach((name) => {
        if (enabled) {
            current.add(name);
        } else {
            current.delete(name);
        }
    });

    drafts[role.name] = [...current];
}

function groupEnabled(roleName: string, permissions: PermissionItem[]): boolean {
    return permissions.every((item) => hasPermission(roleName, item.name));
}

function saveRole(role: RoleRow): void {
    saving[role.name] = true;
    router.put(`/admin/roles/${role.name}`, {
        permissions: drafts[role.name] ?? [],
    }, {
        preserveScroll: true,
        onFinish: () => {
            saving[role.name] = false;
        },
    });
}
</script>

<template>
    <Head title="الأدوار والصلاحيات" />

    <div class="space-y-6 pb-12" dir="rtl">
        <div>
            <div class="mb-1 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-orange-500" />
                <span class="text-xs font-bold text-orange-600 dark:text-orange-400">لوحة التحكم الإدارية</span>
            </div>
            <h1 class="text-2xl font-black text-stone-900 dark:text-white sm:text-3xl">الأدوار والصلاحيات</h1>
            <p class="mt-1 text-xs text-stone-500 dark:text-stone-400 sm:text-sm">
                فعّل أو أوقف كل قسم وكل زرار لكل دور، بما فيها المدير العام.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <section
                v-for="role in roles"
                :key="role.name"
                class="rounded-3xl border border-stone-200 bg-white p-5 shadow-xs dark:border-stone-800 dark:bg-stone-900"
            >
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="flex items-center gap-2 text-lg font-black text-stone-900 dark:text-white">
                            <Shield class="h-5 w-5 text-orange-500" />
                            {{ role.label }}
                        </h2>
                        <p class="mt-1 font-mono text-[11px] text-stone-400">{{ role.name }}</p>
                    </div>
                    <div class="flex items-center gap-1 rounded-xl bg-stone-100 px-2.5 py-1 text-xs font-bold text-stone-600 dark:bg-stone-800 dark:text-stone-300">
                        <Users class="h-3.5 w-3.5" />
                        {{ role.users_count }}
                    </div>
                </div>

                <div class="space-y-4">
                    <div v-for="group in catalog" :key="`${role.name}-${group.key}`">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <p class="text-xs font-black text-stone-700 dark:text-stone-200">{{ group.label }}</p>
                            <button
                                type="button"
                                class="text-[11px] font-bold text-orange-600 dark:text-orange-400"
                                @click="setGroup(role, group.permissions, !groupEnabled(role.name, group.permissions))"
                            >
                                {{ groupEnabled(role.name, group.permissions) ? 'إلغاء المجموعة' : 'تحديد المجموعة' }}
                            </button>
                        </div>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <label
                                v-for="permission in group.permissions"
                                :key="`${role.name}-${permission.name}`"
                                :class="[
                                    'flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-bold',
                                    hasPermission(role.name, permission.name)
                                        ? 'border-orange-200 bg-orange-50 text-stone-800 dark:border-orange-900 dark:bg-orange-950/30 dark:text-stone-100'
                                        : 'border-stone-200 bg-stone-50 text-stone-500 dark:border-stone-800 dark:bg-stone-950 dark:text-stone-400',
                                    'cursor-pointer',
                                ]"
                            >
                                <input
                                    type="checkbox"
                                    class="accent-orange-600"
                                    :checked="hasPermission(role.name, permission.name)"
                                    @change="togglePermission(role, permission.name)"
                                />
                                <span>{{ permission.label }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    class="mt-5 w-full rounded-2xl bg-orange-600 py-2.5 text-xs font-black text-white transition hover:bg-orange-700 disabled:opacity-60"
                    :disabled="saving[role.name]"
                    @click="saveRole(role)"
                >
                    {{ saving[role.name] ? 'جاري الحفظ...' : 'حفظ صلاحيات هذا الدور' }}
                </button>
            </section>
        </div>
    </div>
</template>
