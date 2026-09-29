<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { AlertTriangle, Trash2, X, CheckCircle } from '@lucide/vue';

const props = withDefaults(
    defineProps<{
        isOpen: boolean;
        onConfirm?: () => void;
        onCancel?: () => void;
        title?: string;
        message: string;
        confirmText?: string;
        cancelText?: string;
        variant?: 'danger' | 'warning' | 'info';
    }>(),
    {
        confirmText: 'تأكيد',
        cancelText: 'إلغاء',
        variant: 'danger',
    },
);

const emit = defineEmits<{
    confirm: [];
    cancel: [];
}>();

const handleConfirm = (): void => {
    props.onConfirm?.();
    emit('confirm');
};

const handleCancel = (): void => {
    props.onCancel?.();
    emit('cancel');
};

const styles = computed(() => {
    const map = {
        danger: {
            iconBg: 'bg-red-100 dark:bg-red-950/60',
            button: 'bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 shadow-red-600/30',
            title: props.title || 'تأكيد الحذف',
            icon: 'danger' as const,
        },
        warning: {
            iconBg: 'bg-amber-100 dark:bg-amber-950/60',
            button: 'bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-700 hover:to-amber-600 shadow-amber-600/30',
            title: props.title || 'تحذير',
            icon: 'warning' as const,
        },
        info: {
            iconBg: 'bg-blue-100 dark:bg-blue-950/60',
            button: 'bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 shadow-blue-600/30',
            title: props.title || 'تأكيد العملية',
            icon: 'info' as const,
        },
    };

    return map[props.variant];
});

const onKeydown = (e: KeyboardEvent): void => {
    if (e.key === 'Escape') {
        handleCancel();
    }
};

watch(
    () => props.isOpen,
    (open) => {
        if (open) {
            document.addEventListener('keydown', onKeydown);
        } else {
            document.removeEventListener('keydown', onKeydown);
        }
    },
);

onMounted(() => {
    if (props.isOpen) {
        document.addEventListener('keydown', onKeydown);
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
    >
        <div class="absolute inset-0 bg-stone-950/60 backdrop-blur-sm" @click="handleCancel" />
        <div
            class="relative bg-white dark:bg-stone-900 rounded-3xl shadow-2xl shadow-stone-900/30 w-full max-w-sm border border-stone-100 dark:border-stone-800"
            style="animation: slideUp 0.2s ease"
        >
            <button
                type="button"
                class="absolute top-4 left-4 p-1.5 rounded-xl text-stone-400 hover:text-stone-600 dark:hover:text-stone-200 hover:bg-stone-100 dark:hover:bg-stone-800 transition"
                @click="handleCancel"
            >
                <X class="w-4 h-4" />
            </button>
            <div class="p-8 pt-7 flex flex-col items-center text-center gap-4">
                <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center', styles.iconBg]">
                    <Trash2 v-if="styles.icon === 'danger'" class="w-6 h-6 text-red-500" />
                    <AlertTriangle v-else-if="styles.icon === 'warning'" class="w-6 h-6 text-amber-500" />
                    <CheckCircle v-else class="w-6 h-6 text-blue-500" />
                </div>
                <div>
                    <h3 class="text-base font-black text-stone-900 dark:text-white mb-1.5">{{ styles.title }}</h3>
                    <p class="text-sm text-stone-500 dark:text-stone-400 leading-relaxed">{{ message }}</p>
                </div>
                <div class="flex gap-3 w-full mt-1">
                    <button
                        type="button"
                        class="flex-1 px-4 py-3 rounded-2xl text-sm font-bold text-stone-600 dark:text-stone-300 bg-stone-100 dark:bg-stone-800 hover:bg-stone-200 dark:hover:bg-stone-700 transition"
                        @click="handleCancel"
                    >
                        {{ cancelText }}
                    </button>
                    <button
                        type="button"
                        :class="['flex-1 px-4 py-3 rounded-2xl text-sm font-bold text-white shadow-lg transition-all duration-200', styles.button]"
                        @click="handleConfirm"
                    >
                        {{ confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(16px) scale(0.97);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>
