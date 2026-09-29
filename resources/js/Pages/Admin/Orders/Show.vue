<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, MapPin, ShoppingBag, Clock, User, DollarSign, CheckCircle } from '@lucide/vue';

interface Order {
    id: number;
    order_number: string;
    status: string;
    subtotal_amount: number;
    delivery_fee: number;
    discount_amount: number;
    total_amount: number;
    payment_method: string;
    payment_status: string;
    delivery_address: {
        street?: string;
        city?: string;
        notes?: string;
    } | null;
    notes: string | null;
    created_at: string;
    restaurant: { id: number; name: string; slug: string };
    customer: { id: number; user: { name: string; email: string; phone?: string } };
    deliveryDriver: { id: number; user: { name: string } } | null;
    items: Array<{
        id: number;
        quantity: number;
        unit_price: number;
        total_price: number;
        menuItem: { name: string };
        notes: string | null;
    }>;
    statusHistory: Array<{
        id: number;
        status: string;
        note: string | null;
        created_at: string;
        user?: { name: string };
    }>;
}

const props = defineProps<{
    order: Order;
}>();

const statusMap: Record<string, { label: string; cls: string }> = {
    PENDING: { label: 'في الانتظار', cls: 'bg-amber-500/20 text-amber-400 border-amber-500/30' },
    CONFIRMED: { label: 'مؤكد', cls: 'bg-indigo-500/20 text-indigo-400 border-indigo-500/30' },
    PREPARING: { label: 'قيد التحضير', cls: 'bg-blue-500/20 text-blue-400 border-blue-500/30' },
    OUT_FOR_DELIVERY: { label: 'في الطريق', cls: 'bg-purple-500/20 text-purple-400 border-purple-500/30' },
    DELIVERED: { label: 'تم التوصيل', cls: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' },
    CANCELLED: { label: 'ملغى', cls: 'bg-red-500/20 text-red-400 border-red-500/30' },
};

const sc = computed(() => statusMap[props.order.status] ?? statusMap.PENDING);

const fmt = (v: number): string => (v / 100).toFixed(2);

const historyStatus = (status: string) => statusMap[status] ?? statusMap.PENDING;
</script>

<template>
    <Head :title="`طلب #${order.order_number}`" />

    <div class="space-y-6" dir="rtl">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link
                    href="/admin/orders"
                    class="p-2 rounded-lg bg-white hover:bg-stone-100 text-stone-400 hover:text-stone-900 transition-colors"
                >
                    <ArrowLeft class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-stone-900">طلب #{{ order.order_number }}</h1>
                    <p class="text-stone-400 text-sm mt-1">{{ new Date(order.created_at).toLocaleString('ar-EG') }}</p>
                </div>
                <span :class="['px-3 py-1 rounded-full text-xs font-semibold border', sc.cls]">{{ sc.label }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                    <h2 class="text-lg font-semibold text-stone-900 mb-4 flex items-center gap-2">
                        <ShoppingBag class="w-5 h-5 text-orange-400" />
                        عناصر الطلب
                    </h2>
                    <div class="space-y-3">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="flex items-center justify-between p-3 bg-white rounded-lg"
                        >
                            <div>
                                <p class="text-stone-900 font-medium text-sm">{{ item.menuItem.name }}</p>
                                <p v-if="item.notes" class="text-stone-400 text-xs mt-0.5">{{ item.notes }}</p>
                            </div>
                            <div class="text-left">
                                <p class="text-stone-300 text-sm">x{{ item.quantity }} × {{ fmt(item.unit_price) }} ج</p>
                                <p class="text-stone-900 font-semibold text-sm">{{ fmt(item.total_price) }} ج</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-stone-200 space-y-2">
                        <div class="flex justify-between text-sm text-stone-300">
                            <span>المجموع الفرعي</span>
                            <span>{{ fmt(order.subtotal_amount) }} ج</span>
                        </div>
                        <div class="flex justify-between text-sm text-stone-300">
                            <span>رسوم التوصيل</span>
                            <span>{{ fmt(order.delivery_fee) }} ج</span>
                        </div>
                        <div v-if="order.discount_amount > 0" class="flex justify-between text-sm text-emerald-400">
                            <span>الخصم</span>
                            <span>- {{ fmt(order.discount_amount) }} ج</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold text-stone-900 pt-2 border-t border-stone-200">
                            <span>الإجمالي</span>
                            <span>{{ fmt(order.total_amount) }} ج</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                    <h2 class="text-lg font-semibold text-stone-900 mb-4 flex items-center gap-2">
                        <Clock class="w-5 h-5 text-indigo-400" />
                        سجل الحالات
                    </h2>
                    <div class="relative space-y-4">
                        <div
                            v-for="h in order.statusHistory"
                            :key="h.id"
                            class="flex items-start gap-4"
                        >
                            <div
                                :class="[
                                    'w-8 h-8 rounded-full border flex items-center justify-center shrink-0',
                                    historyStatus(h.status).cls,
                                ]"
                            >
                                <CheckCircle class="w-4 h-4" />
                            </div>
                            <div class="flex-1 pb-4 border-b border-stone-200 last:border-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-stone-900 font-medium text-sm">{{ historyStatus(h.status).label }}</span>
                                    <span class="text-stone-500 text-xs">{{ new Date(h.created_at).toLocaleString('ar-EG') }}</span>
                                </div>
                                <p v-if="h.note" class="text-stone-400 text-xs mt-1">{{ h.note }}</p>
                                <p v-if="h.user" class="text-stone-500 text-xs mt-0.5">بواسطة: {{ h.user.name }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                    <h3 class="text-sm font-semibold text-stone-300 mb-3">المطعم</h3>
                    <Link
                        :href="`/admin/restaurants/${order.restaurant.id}`"
                        class="text-orange-400 hover:text-orange-300 font-medium transition-colors"
                    >
                        {{ order.restaurant.name }}
                    </Link>
                </div>

                <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                    <h3 class="text-sm font-semibold text-stone-300 mb-3 flex items-center gap-2">
                        <User class="w-4 h-4" /> العميل
                    </h3>
                    <p class="text-stone-900 font-medium">{{ order.customer.user.name }}</p>
                    <p class="text-stone-400 text-sm mt-1">{{ order.customer.user.email }}</p>
                    <p v-if="order.customer.user.phone" class="text-stone-400 text-sm mt-0.5">{{ order.customer.user.phone }}</p>
                </div>

                <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                    <h3 class="text-sm font-semibold text-stone-300 mb-3 flex items-center gap-2">
                        <MapPin class="w-4 h-4" /> عنوان التوصيل
                    </h3>
                    <div v-if="order.delivery_address" class="text-sm text-stone-300 space-y-1">
                        <p v-if="order.delivery_address.street">{{ order.delivery_address.street }}</p>
                        <p v-if="order.delivery_address.city">{{ order.delivery_address.city }}</p>
                        <p v-if="order.delivery_address.notes" class="text-stone-400">{{ order.delivery_address.notes }}</p>
                    </div>
                    <p v-else class="text-stone-500 text-sm">غير محدد</p>
                </div>

                <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                    <h3 class="text-sm font-semibold text-stone-300 mb-3">السائق</h3>
                    <p v-if="order.deliveryDriver" class="text-stone-900 font-medium">{{ order.deliveryDriver.user.name }}</p>
                    <p v-else class="text-stone-500 text-sm">لم يتم التعيين بعد</p>
                </div>

                <div class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                    <h3 class="text-sm font-semibold text-stone-300 mb-3 flex items-center gap-2">
                        <DollarSign class="w-4 h-4" /> الدفع
                    </h3>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-stone-400">الطريقة</span>
                            <span class="text-stone-900">{{ order.payment_method === 'CASH' ? 'نقدي' : order.payment_method }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-stone-400">الحالة</span>
                            <span :class="order.payment_status === 'PAID' ? 'text-emerald-400' : 'text-amber-400'">
                                {{ order.payment_status === 'PAID' ? 'مدفوع' : 'في الانتظار' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div v-if="order.notes" class="bg-white border border-stone-200 rounded-2xl p-6 shadow-xs">
                    <h3 class="text-sm font-semibold text-stone-300 mb-2">ملاحظات</h3>
                    <p class="text-stone-300 text-sm">{{ order.notes }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
