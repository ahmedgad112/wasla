<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Database, Plus, Trash2, Download, HardDrive, Clock, CheckCircle, AlertCircle } from '@lucide/vue';
import ConfirmModal from '../../../Components/ConfirmModal.vue';

interface Backup {
    id: number;
    filename: string;
    disk: string;
    size: number;
    status: string;
    created_at: string;
    created_by: { name: string } | null;
}

defineProps<{
    backups: Backup[];
}>();

const confirmCreate = ref(false);
const confirmDeleteId = ref<number | null>(null);

const fmtSize = (bytes: number): string => {
    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const doCreate = (): void => {
    router.post('/admin/backups');
    confirmCreate.value = false;
};

const doDelete = (): void => {
    if (confirmDeleteId.value !== null) {
        router.delete(`/admin/backups/${confirmDeleteId.value}`);
        confirmDeleteId.value = null;
    }
};
</script>

<template>
    <Head title="النسخ الاحتياطية" />

    <div class="space-y-6" dir="rtl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-stone-900 flex items-center gap-2">
                    <HardDrive class="w-6 h-6 text-orange-400" />
                    النسخ الاحتياطية
                </h1>
                <p class="text-stone-400 text-sm mt-1">إدارة نسخ قاعدة البيانات الاحتياطية</p>
            </div>
            <button
                class="flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-400 text-white rounded-lg font-medium transition-colors"
                @click="confirmCreate = true"
            >
                <Plus class="w-4 h-4" />
                نسخة احتياطية جديدة
            </button>
        </div>

        <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4">
            <p class="text-amber-300 text-sm">
                💡 يتم حفظ النسخ الاحتياطية على القرص المحلي. يُنصح بنقلها إلى تخزين خارجي بشكل دوري.
            </p>
        </div>

        <div class="bg-white border border-stone-200 rounded-2xl overflow-hidden shadow-xs">
            <div v-if="backups.length === 0" class="py-16 text-center">
                <Database class="w-12 h-12 text-stone-600 mx-auto mb-3" />
                <p class="text-stone-400">لا توجد نسخ احتياطية بعد</p>
                <button
                    class="mt-4 px-4 py-2 bg-orange-500 hover:bg-orange-400 text-white rounded-lg text-sm font-medium transition-colors"
                    @click="confirmCreate = true"
                >
                    إنشاء أول نسخة
                </button>
            </div>
            <div v-else class="divide-y divide-white/5">
                <div
                    v-for="backup in backups"
                    :key="backup.id"
                    class="flex items-center justify-between px-6 py-4 hover:bg-white transition-colors"
                >
                    <div class="flex items-center gap-4">
                        <div
                            :class="[
                                'p-2 rounded-lg',
                                backup.status === 'completed'
                                    ? 'bg-emerald-500/20'
                                    : backup.status === 'failed'
                                      ? 'bg-red-500/20'
                                      : 'bg-amber-500/20',
                            ]"
                        >
                            <CheckCircle v-if="backup.status === 'completed'" class="w-5 h-5 text-emerald-400" />
                            <AlertCircle v-else-if="backup.status === 'failed'" class="w-5 h-5 text-red-400" />
                            <Clock v-else class="w-5 h-5 text-amber-400" />
                        </div>
                        <div>
                            <p class="text-stone-900 font-medium text-sm">{{ backup.filename }}</p>
                            <div class="flex items-center gap-3 mt-0.5">
                                <span class="text-stone-400 text-xs">{{ fmtSize(backup.size) }}</span>
                                <span class="text-stone-500 text-xs">•</span>
                                <span class="text-stone-400 text-xs">{{ backup.disk }}</span>
                                <template v-if="backup.created_by">
                                    <span class="text-stone-500 text-xs">•</span>
                                    <span class="text-stone-400 text-xs">{{ backup.created_by.name }}</span>
                                </template>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-stone-400 text-sm">{{ new Date(backup.created_at).toLocaleString('ar-EG') }}</span>
                        <div class="flex items-center gap-2">
                            <button class="p-1.5 text-stone-400 hover:text-indigo-400 hover:bg-indigo-500/10 rounded transition-colors">
                                <Download class="w-4 h-4" />
                            </button>
                            <button
                                class="p-1.5 text-stone-400 hover:text-red-400 hover:bg-red-500/10 rounded transition-colors"
                                @click="confirmDeleteId = backup.id"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <ConfirmModal
        :is-open="confirmCreate"
        title="إنشاء نسخة احتياطية"
        message="هل تريد بدء عملية إنشاء نسخة احتياطية لقاعدة البيانات الآن؟ قد يستغرق ذلك بضع لحظات."
        confirm-text="بدء النسخ الاحتياطي"
        cancel-text="إلغاء"
        variant="info"
        @confirm="doCreate"
        @cancel="confirmCreate = false"
    />

    <ConfirmModal
        :is-open="confirmDeleteId !== null"
        title="حذف النسخة الاحتياطية"
        message="هل أنت متأكد من حذف هذا الملف الاحتياطي نهائياً؟ لا يمكن استعادته بعد الحذف."
        confirm-text="نعم، احذف الملف"
        cancel-text="إلغاء"
        variant="danger"
        @confirm="doDelete"
        @cancel="confirmDeleteId = null"
    />
</template>
