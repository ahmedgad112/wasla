<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '../../Layouts/CustomerLayout.vue';
import type { Order, PaginatedResponse } from '../../Types';
import { ShoppingBag, ArrowRight } from '@lucide/vue';

const props = defineProps<{
    orders: PaginatedResponse<Order>;
}>();

const items = computed(() => props.orders?.data || []);

const statusMap: Record<string, { label: string; bg: string; text: string }> = {
    PENDING: { label: 'قيد الانتظار', bg: 'bg-amber-100 dark:bg-amber-950', text: 'text-amber-800 dark:text-amber-300' },
    CONFIRMED: { label: 'تم التأكيد', bg: 'bg-blue-100 dark:bg-blue-950', text: 'text-blue-800 dark:text-blue-300' },
    PREPARING: { label: 'جارٍ التجهيز', bg: 'bg-orange-100 dark:bg-orange-950', text: 'text-orange-800 dark:text-orange-300' },
    READY_FOR_PICKUP: { label: 'جاهز للاستلام', bg: 'bg-purple-100 dark:bg-purple-950', text: 'text-purple-800 dark:text-purple-300' },
    ASSIGNED_TO_DRIVER: { label: 'تم تعيين الطيار', bg: 'bg-indigo-100 dark:bg-indigo-950', text: 'text-indigo-800 dark:text-indigo-300' },
    OUT_FOR_DELIVERY: { label: 'الطيار في الطريق إليك', bg: 'bg-teal-100 dark:bg-teal-950', text: 'text-teal-800 dark:text-teal-300' },
    DELIVERED: { label: 'تم التوصيل بنجاح', bg: 'bg-emerald-100 dark:bg-emerald-950', text: 'text-emerald-800 dark:text-emerald-300' },
    CANCELLED: { label: 'ملغي', bg: 'bg-red-100 dark:bg-red-950', text: 'text-red-800 dark:text-red-300' },
};

const getStatusMeta = (status: string) =>
    statusMap[status] || { label: status, bg: 'bg-stone-100', text: 'text-stone-700' };

const formatOrderDate = (date: string): string =>
    new Date(date).toLocaleDateString('ar-EG', { hour: '2-digit', minute: '2-digit' });
</script>

<template>
    <CustomerLayout title="سجل طلباتي">
        <Head title="سجل طلباتي" />

        <div class="bg-white dark:bg-stone-900 rounded-3xl border border-stone-200 dark:border-stone-800 p-6 shadow-xs">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-stone-100 dark:border-stone-800">
                <div>
                    <h1 class="text-xl font-black text-stone-900 dark:text-white">
                        سجل جميع الطلبات
                    </h1>
                    <p class="text-xs text-stone-500 dark:text-stone-400 mt-1">
                        تتبع حالة طلباتك الحالية وتفاصيل الفواتير السابقة
                    </p>
                </div>
                <Link
                    href="/restaurants"
                    class="px-4 py-2 rounded-xl bg-orange-600 text-white font-bold text-xs shadow-xs hover:bg-orange-700 transition"
                >
                    طلب جديد
                </Link>
            </div>

            <div v-if="items.length === 0" class="text-center py-16">
                <ShoppingBag class="w-16 h-16 text-stone-300 dark:text-stone-600 mx-auto mb-4" />
                <h3 class="text-base font-bold text-stone-700 dark:text-stone-300 mb-1">
                    لا توجد طلبات سابقة حتى الآن
                </h3>
                <p class="text-xs text-stone-400 mb-6">
                    ابدأ باكتشاف مطاعم برج العرب واطلب فطارك وسحورك الآن
                </p>
                <Link
                    href="/restaurants"
                    class="px-5 py-2.5 rounded-xl bg-orange-600 text-white font-bold text-xs"
                >
                    تصفح المطاعم
                </Link>
            </div>
            <div v-else class="divide-y divide-stone-100 dark:divide-stone-800">
                <div
                    v-for="order in items"
                    :key="order.id"
                    class="py-5 flex flex-col md:flex-row md:items-center justify-between gap-4"
                >
                    <div class="space-y-1">
                        <div class="flex items-center gap-3">
                            <h3 class="font-extrabold text-sm text-stone-900 dark:text-white">
                                {{ order.restaurant?.name || 'مطعم شريك' }}
                            </h3>
                            <span
                                class="px-2.5 py-0.5 rounded-full text-xs font-bold"
                                :class="[getStatusMeta(order.status).bg, getStatusMeta(order.status).text]"
                            >
                                {{ getStatusMeta(order.status).label }}
                            </span>
                        </div>
                        <p class="text-xs text-stone-400 font-mono">
                            رقم الطلب: {{ order.order_number }} • {{ formatOrderDate(order.created_at) }}
                        </p>
                        <p class="text-xs text-stone-500 truncate max-w-md">
                            العنوان: {{ order.address }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between md:justify-end gap-6 pt-2 md:pt-0">
                        <div class="text-left">
                            <span class="text-[10px] text-stone-400 block">الإجمالي المطلوب</span>
                            <span class="text-base font-black text-stone-900 dark:text-white">
                                {{ order.total_amount }} ج.م
                            </span>
                        </div>

                        <Link
                            :href="`/customer/orders/${order.order_number}`"
                            class="px-4 py-2 rounded-xl bg-stone-100 dark:bg-stone-800 hover:bg-orange-50 dark:hover:bg-orange-950/40 hover:text-orange-600 text-xs font-bold transition flex items-center gap-1"
                        >
                            <span>عرض التفاصيل</span>
                            <ArrowRight class="w-3.5 h-3.5 rotate-180" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>
