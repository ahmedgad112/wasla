<script setup lang="ts">
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useCartStore } from '../Stores/cartStore';
import type { SharedInertiaProps } from '../Types';
import {
    X,
    Plus,
    Minus,
    Trash2,
    ShoppingBag,
    CheckCircle2,
    Lock,
    GraduationCap,
} from '@lucide/vue';

const page = usePage<SharedInertiaProps>();
const auth = computed(() => page.props.auth);
const cart = useCartStore();

const itemCount = computed(() => cart.getItemCount);
const subtotal = computed(() => cart.getSubtotal);
const studentDiscount = computed(() => cart.getStudentDiscountAmount);
const deliveryFee = computed(() => cart.getDeliveryFee);
const total = computed(() => cart.getTotal);

const handleCheckoutRedirect = (): void => {
    cart.closeCart();
    router.visit('/cart');
};

const onImageError = (event: Event): void => {
    (event.target as HTMLImageElement).src = '/images/sandwich-foul.jpg';
};
</script>

<template>
    <div v-if="cart.isCartOpen" class="fixed inset-0 z-50 flex flex-col justify-end">
        <button type="button" aria-label="إغلاق السلة" class="absolute inset-0 bg-black/55" @click="cart.closeCart()" />

        <div class="relative z-10 mx-auto flex max-h-[88%] w-full max-w-lg flex-col rounded-t-3xl bg-white shadow-2xl dark:bg-stone-900">
            <div class="mx-auto mt-2 h-1.5 w-10 rounded-full bg-stone-300 dark:bg-stone-700" />

            <div class="flex items-center justify-between px-4 py-3">
                <div>
                    <h2 class="text-base font-black text-stone-900 dark:text-white">سلتك</h2>
                    <p class="text-[11px] text-stone-500">
                        {{ itemCount }} صنف{{ cart.restaurant ? ` • ${cart.restaurant.name}` : '' }}
                    </p>
                </div>
                <div class="flex items-center gap-1">
                    <button
                        v-if="cart.items.length > 0"
                        type="button"
                        class="rounded-xl p-2 text-stone-400 hover:bg-red-50 hover:text-red-500"
                        @click="cart.clearCart()"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        class="rounded-xl p-2 text-stone-400 hover:bg-stone-100 dark:hover:bg-stone-800"
                        @click="cart.closeCart()"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-4 pb-3">
                <div v-if="cart.items.length === 0" class="flex flex-col items-center py-10 text-center">
                    <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-2xl bg-orange-50 text-orange-500 dark:bg-orange-950/40">
                        <ShoppingBag class="h-8 w-8" />
                    </div>
                    <h3 class="font-black text-stone-800 dark:text-white">السلة فارغة</h3>
                    <p class="mt-1 max-w-[220px] text-xs text-stone-500">
                        اختار وجبتك من مطاعم الجامعة وأضفها هنا
                    </p>
                    <Link
                        href="/restaurants"
                        class="mt-4 rounded-2xl bg-orange-500 px-5 py-2.5 text-sm font-black text-white"
                        @click="cart.closeCart()"
                    >
                        تصفح المطاعم
                    </Link>
                </div>
                <div v-else class="divide-y divide-stone-100 dark:divide-stone-800">
                    <div v-for="item in cart.items" :key="item.id" class="flex gap-3 py-3">
                        <img
                            :src="item.menuItem.image || '/images/sandwich-foul.jpg'"
                            :alt="item.menuItem.name"
                            class="h-14 w-14 rounded-xl object-cover"
                            @error="onImageError"
                        />
                        <div class="min-w-0 flex-1">
                            <h4 class="truncate text-sm font-black text-stone-900 dark:text-white">
                                {{ item.menuItem.name }}
                            </h4>
                            <p
                                v-if="item.selectedOptions?.length > 0"
                                class="truncate text-[10px] text-stone-400"
                            >
                                {{ item.selectedOptions.map((o) => o.valueName).join(' • ') }}
                            </p>
                            <div class="mt-1.5 flex items-center gap-2">
                                <div class="flex items-center rounded-lg bg-stone-100 dark:bg-stone-800">
                                    <button
                                        type="button"
                                        class="grid h-7 w-7 place-items-center"
                                        @click="cart.updateQuantity(item.id, item.quantity - 1)"
                                    >
                                        <Minus class="h-3.5 w-3.5" />
                                    </button>
                                    <span class="w-5 text-center text-xs font-black">
                                        {{ item.quantity }}
                                    </span>
                                    <button
                                        type="button"
                                        class="grid h-7 w-7 place-items-center"
                                        @click="cart.updateQuantity(item.id, item.quantity + 1)"
                                    >
                                        <Plus class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    class="text-stone-300 hover:text-red-500"
                                    @click="cart.removeItem(item.id)"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                        <p class="text-sm font-black text-stone-900 dark:text-white">
                            {{ item.totalPrice.toFixed(0) }} ج
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="cart.items.length > 0"
                class="space-y-3 border-t border-stone-100 px-4 pb-[max(1rem,env(safe-area-inset-bottom))] pt-3 dark:border-stone-800"
            >
                <div
                    v-if="!auth?.user"
                    class="flex items-center gap-2 rounded-xl bg-amber-50 px-3 py-2 text-[11px] text-amber-800 dark:bg-amber-950/30 dark:text-amber-200"
                >
                    <Lock class="h-3.5 w-3.5 shrink-0" />
                    سجل دخولك عشان تأكد الطلب وتاخد خصم الطلاب
                </div>
                <div
                    v-if="studentDiscount > 0"
                    class="flex items-center justify-between text-xs font-bold text-emerald-700"
                >
                    <span class="flex items-center gap-1">
                        <GraduationCap class="h-3.5 w-3.5" />
                        خصم الطلاب ({{ cart.studentDiscountPercentage }}%)
                    </span>
                    <span>-{{ studentDiscount.toFixed(0) }} ج.م</span>
                </div>
                <div class="flex items-center justify-between text-xs text-stone-500">
                    <span>المجموع الفرعي</span>
                    <span class="font-bold text-stone-800 dark:text-stone-200">{{ subtotal.toFixed(0) }} ج.م</span>
                </div>
                <div
                    v-if="deliveryFee > 0"
                    class="flex items-center justify-between text-xs text-stone-500"
                >
                    <span>التوصيل</span>
                    <span class="font-bold text-stone-800 dark:text-stone-200">{{ deliveryFee.toFixed(0) }} ج.م</span>
                </div>
                <div class="flex items-center justify-between text-sm font-black">
                    <span>الإجمالي</span>
                    <span class="text-orange-600">{{ total.toFixed(0) }} ج.م</span>
                </div>
                <button
                    v-if="!$page.props.auth.user || $page.props.auth.user.role !== 'CUSTOMER' || $can('customer.orders')"
                    type="button"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-orange-500 to-amber-500 py-3.5 text-sm font-black text-white shadow-lg shadow-orange-500/25"
                    @click="handleCheckoutRedirect"
                >
                    <CheckCircle2 class="h-4 w-4" />
                    إتمام الطلب
                </button>
            </div>
        </div>
    </div>
</template>
