<script setup lang="ts">
import type { Customer } from '../Types';
import GuestLayout from './GuestLayout.vue';

defineProps<{
    title?: string;
    customer?: Customer;
}>();
</script>

<template>
    <GuestLayout>
        <div class="px-4 py-4">
            <slot />
        </div>
    </GuestLayout>
</template>
