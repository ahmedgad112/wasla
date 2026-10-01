<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import type { DeliveryDriver, Order } from '../../Types';
import DeliveryRouteMap from '../../Components/DeliveryRouteMap.vue';
import { Bike, Phone, MapPin, Store, CheckCircle2, ArrowRight } from '@lucide/vue';

const props = defineProps<{
    order: Order;
    driver: DeliveryDriver;
}>();

const handleUpdateStatus = (nextStatus: 'OUT_FOR_DELIVERY' | 'DELIVERED'): void => {
    router.patch(`/delivery/orders/${props.order.id}/status`, {
        status: nextStatus,
    });
};
</script>

<template>
        <Head :title="`مهمة التوصيل ${order.order_number}`" />

        <div class="space-y-6">
            <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-xs font-mono font-bold text-emerald-600 block">{{ order.order_number }}</span>
                    <h1 class="text-lg font-black text-stone-900 dark:text-white mt-0.5">مهمة توصيل نشطة</h1>
                </div>
                <Link
                    href="/delivery/dashboard"
                    class="text-xs font-bold text-stone-500 hover:text-stone-900 dark:hover:text-white flex items-center gap-1"
                >
                    <span>الرجوع للرئيسية</span>
                    <ArrowRight class="w-4 h-4" />
                </Link>
            </div>

            <div class="p-6 rounded-3xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-lg space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs uppercase font-bold text-emerald-100 tracking-wider">الحالة الحالية للطلب</span>
                    <span class="px-3 py-1 rounded-full bg-white/20 text-white text-xs font-extrabold backdrop-blur-sm">
                        {{ order.status === 'OUT_FOR_DELIVERY' ? 'في الطريق إلى العميل' : 'مكلف بالاستلام من المطعم' }}
                    </span>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <button
                        v-if="order.status === 'ASSIGNED_TO_DRIVER'"
                        type="button"
                        class="flex-1 py-3 px-4 rounded-xl bg-white text-emerald-800 hover:bg-emerald-50 font-black text-xs shadow transition flex items-center justify-center gap-2"
                        @click="handleUpdateStatus('OUT_FOR_DELIVERY')"
                    >
                        <Bike class="w-4 h-4" />
                        <span>استلمت الوجبات وبدأت التحرك للعنوان</span>
                    </button>

                    <button
                        v-if="order.status === 'OUT_FOR_DELIVERY'"
                        type="button"
                        class="flex-1 py-3.5 px-4 rounded-xl bg-amber-400 hover:bg-amber-300 text-stone-950 font-black text-sm shadow-lg transition flex items-center justify-center gap-2"
                        @click="handleUpdateStatus('DELIVERED')"
                    >
                        <CheckCircle2 class="w-5 h-5 text-stone-950" />
                        <span>تأكيد تسليم الطلب وتحصيل {{ order.total_amount }} ج.م كاش</span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-black text-stone-900 dark:text-white flex items-center gap-2">
                            <Store class="w-4 h-4 text-orange-500" />
                            <span>المطعم: {{ order.restaurant?.name }}</span>
                        </h2>
                        <a
                            v-if="order.restaurant?.phone"
                            :href="`tel:${order.restaurant.phone}`"
                            class="px-3 py-1.5 rounded-xl bg-orange-50 dark:bg-orange-950/40 text-orange-600 text-xs font-bold flex items-center gap-1"
                        >
                            <Phone class="w-3.5 h-3.5" />
                            <span>اتصال بالمطعم</span>
                        </a>
                    </div>
                    <p class="text-xs text-stone-600 dark:text-stone-400">
                        {{ order.restaurant?.address || 'برج العرب الجديدة' }}
                    </p>
                </div>

                <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-black text-stone-900 dark:text-white flex items-center gap-2">
                            <MapPin class="w-4 h-4 text-emerald-500" />
                            <span>العميل: {{ order.customer?.user?.name }}</span>
                        </h2>
                        <a
                            v-if="order.customer?.user?.phone"
                            :href="`tel:${order.customer.user.phone}`"
                            class="px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 text-xs font-bold flex items-center gap-1"
                        >
                            <Phone class="w-3.5 h-3.5" />
                            <span>اتصال بالعميل</span>
                        </a>
                    </div>
                    <p class="text-xs font-medium text-stone-800 dark:text-stone-200 leading-relaxed">{{ order.address }}</p>
                    <p v-if="order.customer_notes" class="text-xs text-amber-600 dark:text-amber-400 italic">
                        ملاحظة العميل: {{ order.customer_notes }}
                    </p>
                </div>
            </div>

            <DeliveryRouteMap
                :restaurant-lat="Number(order.restaurant?.latitude) || 30.87"
                :restaurant-lng="Number(order.restaurant?.longitude) || 29.58"
                :restaurant-name="order.restaurant?.name || 'المطعم'"
                :restaurant-address="order.restaurant?.address"
                :restaurant-phone="order.restaurant?.phone"
                :customer-lat="order.latitude ? Number(order.latitude) : undefined"
                :customer-lng="order.longitude ? Number(order.longitude) : undefined"
                :customer-address="order.address"
                :customer-name="order.customer?.user?.name || 'العميل'"
                :customer-phone="order.customer?.user?.phone"
                :order-status="order.status"
                :order-id="order.id"
            />

            <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs space-y-4">
                <h2 class="text-base font-black text-stone-900 dark:text-white">قائمة الوجبات للاستلام والمطابقة</h2>
                <div class="divide-y divide-stone-100 dark:divide-stone-800 text-xs">
                    <div
                        v-for="item in order.items || []"
                        :key="item.id"
                        class="py-3 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <span class="w-7 h-7 rounded-lg bg-emerald-100 dark:bg-emerald-950 text-emerald-600 flex items-center justify-center font-bold">
                                {{ item.quantity }}
                            </span>
                            <span class="font-bold text-stone-900 dark:text-white">{{ item.name }}</span>
                        </div>
                        <span class="font-bold text-stone-600 dark:text-stone-300">{{ item.total_price }} ج.م</span>
                    </div>
                </div>
            </div>

            <div class="p-6 rounded-3xl bg-amber-50 dark:bg-stone-800/80 border border-amber-300 dark:border-stone-700 flex items-center justify-between">
                <div>
                    <span class="text-xs text-amber-800 dark:text-amber-300 font-bold block">المطلوب تحصيله نقداً (شامل التوصيل)</span>
                    <p class="text-[11px] text-stone-500 mt-0.5">سلم الوجبة واستلم المبلغ بالكامل</p>
                </div>
                <span class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ order.total_amount }} ج.م</span>
            </div>
        </div>
</template>
