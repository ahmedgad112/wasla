<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    appName?: string;
    supportPhone?: string;
}>();
</script>

<template>
    <Head title="الموقع تحت الصيانة" />

    <div class="flex min-h-[70vh] items-center justify-center px-6 py-16 text-center" dir="rtl">
        <div class="max-w-md">
            <p class="text-sm font-bold text-orange-600">{{ appName || 'وصلة' }}</p>
            <h1 class="mt-2 text-2xl font-black text-stone-900 dark:text-white">الموقع تحت الصيانة</h1>
            <p class="mt-3 text-sm leading-7 text-stone-500">
                بنحدّث الخدمة دلوقتي. ارجع بعد شوية وهتلاقي الموقع شغال تاني.
            </p>
            <a
                v-if="supportPhone"
                :href="`tel:${supportPhone}`"
                class="mt-6 inline-flex rounded-full bg-stone-900 px-4 py-2 text-sm font-bold text-white"
            >
                الدعم: {{ supportPhone }}
            </a>
        </div>
    </div>
</template>
