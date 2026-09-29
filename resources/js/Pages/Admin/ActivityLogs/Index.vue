<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Activity, User, Clock } from '@lucide/vue';

interface Log {
    id: number;
    log_name: string;
    description: string;
    subject_type: string | null;
    subject_id: number | null;
    causer_type: string | null;
    causer_id: number | null;
    causer_name: string | null;
    properties: unknown;
    created_at: string;
}

defineProps<{
    logs: { data: Log[]; total: number; current_page: number; last_page: number };
}>();

const logColors: Record<string, string> = {
    default: 'bg-stone-500/20 text-stone-400',
    auth: 'bg-indigo-500/20 text-indigo-400',
    order: 'bg-orange-500/20 text-orange-400',
    restaurant: 'bg-amber-500/20 text-amber-400',
    finance: 'bg-emerald-500/20 text-emerald-400',
    admin: 'bg-red-500/20 text-red-400',
};

const paginationPages = (lastPage: number): number[] =>
    Array.from({ length: Math.min(5, lastPage) }, (_, i) => i + 1);

const formatDate = (value: string): string => new Date(value).toLocaleString('ar-EG');

const subjectLabel = (type: string | null): string => (type ? (type.split('\\').pop() ?? type) : '');
</script>

<template>
    <Head title="سجل النشاطات" />

    <div class="space-y-6" dir="rtl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 flex items-center gap-2">
                    <Activity class="w-6 h-6 text-orange-400" />
                    سجل النشاطات
                </h1>
                <p class="text-stone-400 text-sm mt-1">{{ logs.total }} نشاط مسجل</p>
            </div>
        </div>

        <div class="bg-white border border-stone-200 rounded-2xl overflow-hidden shadow-xs">
            <div class="divide-y divide-white/5">
                <div
                    v-for="log in logs.data"
                    :key="log.id"
                    class="flex items-start gap-4 px-6 py-4 hover:bg-white transition-colors"
                >
                    <div :class="['mt-0.5 p-2 rounded-lg', logColors[log.log_name] ?? logColors.default]">
                        <Activity class="w-4 h-4" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-stone-900 text-sm">{{ log.description }}</p>
                        <div class="flex items-center gap-4 mt-1.5">
                            <span v-if="log.causer_name" class="flex items-center gap-1 text-xs text-stone-400">
                                <User class="w-3 h-3" />
                                {{ log.causer_name }}
                            </span>
                            <span class="flex items-center gap-1 text-xs text-stone-500">
                                <Clock class="w-3 h-3" />
                                {{ formatDate(log.created_at) }}
                            </span>
                            <span v-if="log.subject_type" class="text-xs text-stone-500">
                                {{ subjectLabel(log.subject_type) }} #{{ log.subject_id }}
                            </span>
                        </div>
                    </div>
                    <span :class="['shrink-0 px-2 py-0.5 rounded text-xs', logColors[log.log_name] ?? logColors.default]">
                        {{ log.log_name }}
                    </span>
                </div>
                <div v-if="logs.data.length === 0" class="py-16 text-center">
                    <Activity class="w-12 h-12 text-stone-600 mx-auto mb-3" />
                    <p class="text-stone-400">لا توجد نشاطات مسجلة</p>
                </div>
            </div>

            <div
                v-if="logs.last_page > 1"
                class="px-6 py-4 border-t border-stone-200 flex items-center justify-between"
            >
                <p class="text-stone-400 text-sm">صفحة {{ logs.current_page }} من {{ logs.last_page }}</p>
                <div class="flex items-center gap-2">
                    <a
                        v-for="page in paginationPages(logs.last_page)"
                        :key="page"
                        :href="`?page=${page}`"
                        :class="[
                            'w-8 h-8 flex items-center justify-center rounded-lg text-sm transition-colors',
                            page === logs.current_page
                                ? 'bg-orange-500 text-white'
                                : 'bg-stone-100 text-stone-500 hover:bg-stone-200',
                        ]"
                    >
                        {{ page }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>
