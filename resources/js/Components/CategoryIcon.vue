<script setup lang="ts">
import { computed, type Component } from 'vue';
import {
    Utensils,
    UtensilsCrossed,
    Pizza,
    Coffee,
    Fish,
    Flame,
    Sparkles,
    ChefHat,
    SunMedium,
    Sandwich,
    Cake,
    Package,
    Drumstick,
    Soup,
    PlusCircle,
    Scale,
    Salad,
} from '@lucide/vue';

const props = withDefaults(
    defineProps<{
        slug?: string;
        name?: string;
        class?: string;
    }>(),
    {
        class: 'w-5 h-5',
    },
);

const icon = computed((): Component => {
    const identifier = (props.slug || props.name || '').toLowerCase();

    if (identifier.includes('box') || identifier.includes('بوكس')) return Package;
    if (identifier.includes('kilo') || identifier.includes('كيلو') || identifier.includes('وزن')) return Scale;
    if (identifier.includes('salad') || identifier.includes('سلط') || identifier.includes('مخلل')) return Salad;
    if (identifier.includes('helw') || identifier.includes('حلو') || identifier.includes('cake')) return Cake;
    if (identifier.includes('sandwich') || identifier.includes('سندوتش')) return Sandwich;
    if (identifier.includes('strip') || identifier.includes('استربس')) return Drumstick;
    if (identifier.includes('cheese') || identifier.includes('جبنة') || identifier.includes('soup') || identifier.includes('شوربة')) return Soup;
    if (identifier.includes('fries') || identifier.includes('بطاطس')) return Flame;
    if (identifier.includes('meal') || identifier.includes('وجبات') || identifier.includes('وجبة')) return Utensils;
    if (identifier.includes('addition') || identifier.includes('إضافات') || identifier.includes('اضافات')) return PlusCircle;
    if (identifier.includes('foul') || identifier.includes('falafel') || identifier.includes('فول') || identifier.includes('فلافل')) return Utensils;
    if (identifier.includes('pizza') || identifier.includes('بيتزا')) return Pizza;
    if (identifier.includes('crepe') || identifier.includes('كريب')) return ChefHat;
    if (identifier.includes('feteer') || identifier.includes('فطير')) return SunMedium;
    if (identifier.includes('shawarma') || identifier.includes('شاورما')) return UtensilsCrossed;
    if (identifier.includes('fish') || identifier.includes('فسيخ') || identifier.includes('رنجة')) return Fish;
    if (identifier.includes('drink') || identifier.includes('مشروب') || identifier.includes('شاي') || identifier.includes('قهوة')) return Coffee;

    return Sparkles;
});
</script>

<template>
    <component :is="icon" :class="props.class" />
</template>
