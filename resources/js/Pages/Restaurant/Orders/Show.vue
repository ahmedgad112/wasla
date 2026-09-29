<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import type { Order, DeliveryDriver } from '../../../Types';
import {
    Bike,
    User,
    MapPin,
    Phone,
    ArrowRight,
} from '@lucide/vue';

const props = withDefaults(
    defineProps<{
        order: Order;
        available_drivers?: DeliveryDriver[];
    }>(),
    {
        available_drivers: () => [],
    },
);

const driver = props.order.delivery_driver || props.order.deliveryDriver;
const selectedDriverId = ref<string | number>(props.order.assigned_delivery_id || '');

const handleAdvanceStatus = (status: string): void => {
    router.patch(`/restaurant/orders/${props.order.id}/status`, { status });
};

const handleAssignDriver = (): void => {
    if (!selectedDriverId.value) {
        return;
    }
    router.post(`/restaurant/orders/${props.order.id}/assign-driver`, {
        driver_id: Number(selectedDriverId.value),
    });
};

const statusMap: Record<string, { label: string; bg: string }> = {
    PENDING: { label: 'بانتظار قبول المطعم', bg: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' },
    CONFIRMED: { label: 'تم التأكيد', bg: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' },
    PREPARING: { label: 'قيد الطهي والتجهيز', bg: 'bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300' },
    READY_FOR_PICKUP: { label: 'جاهز بانتظار الطيار', bg: 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300' },
    OUT_FOR_DELIVERY: { label: 'في الطريق للعميل', bg: 'bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300' },
    DELIVERED: { label: 'تم التسليم بنجاح', bg: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' },
    CANCELLED: { label: 'طلب ملغي', bg: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300' },
};

const statusBadge = (status: string) => statusMap[status] || { label: status, bg: 'bg-stone-100 text-stone-700' };
</script>

<template>
    <Head :title="`تفاصيل طلب ${order.order_number} — بوابة المطعم`" />

    <div class="space-y-6" dir="rtl">
        <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <span class="text-xl font-mono font-black text-orange-600">
                        {{ order.order_number }}
                    </span>
                    <span :class="['px-3 py-1 rounded-full text-xs font-black', statusBadge(order.status).bg]">
                        {{ statusBadge(order.status).label }}
                    </span>
                </div>
                <p class="text-xs text-stone-400 mt-1">
                    تاريخ الاستلام: {{ new Date(order.created_at).toLocaleString('ar-EG') }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button
                    v-if="order.status === 'PENDING'"
                    class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs"
                    @click="handleAdvanceStatus('CONFIRMED')"
                >
                    تأكيد الطلب
                </button>
                <button
                    v-if="order.status === 'CONFIRMED'"
                    class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs"
                    @click="handleAdvanceStatus('PREPARING')"
                >
                    بدء التجهيز بالمطبخ
                </button>
                <button
                    v-if="order.status === 'PREPARING'"
                    class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs"
                    @click="handleAdvanceStatus('READY_FOR_PICKUP')"
                >
                    تم التجهيز — جاهز للاستلام
                </button>

                <Link
                    href="/restaurant/orders"
                    class="text-xs font-bold text-stone-500 hover:text-stone-900 dark:hover:text-white flex items-center gap-1 mr-4"
                >
                    <span>رجوع للطلبات</span>
                    <ArrowRight class="w-4 h-4" />
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs">
                    <h2 class="text-base font-black text-stone-900 dark:text-white mb-4 pb-3 border-b border-stone-100 dark:border-stone-800">
                        محتويات الطلب للمطبخ
                    </h2>

                    <div class="divide-y divide-stone-100 dark:divide-stone-800 text-xs">
                        <div
                            v-for="item in order.items"
                            :key="item.id"
                            class="py-3 flex items-center justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <span class="w-7 h-7 rounded-lg bg-orange-100 dark:bg-orange-950 text-orange-600 flex items-center justify-center font-bold">
                                    {{ item.quantity }}
                                </span>
                                <div>
                                    <h3 class="font-bold text-stone-900 dark:text-white text-sm">
                                        {{ item.name }}
                                    </h3>
                                    <div
                                        v-if="(Array.isArray(item.selected_options) && item.selected_options.length) || (Array.isArray(item.selected_addons) && item.selected_addons.length)"
                                        class="mt-1 space-y-0.5 text-[11px] text-stone-500 dark:text-stone-400"
                                    >
                                        <p
                                            v-for="(option, index) in (Array.isArray(item.selected_options) ? item.selected_options : [])"
                                            :key="`option-${index}`"
                                        >
                                            {{ option.option_name }}: {{ option.value_name }}
                                        </p>
                                        <p
                                            v-for="(addon, index) in (Array.isArray(item.selected_addons) ? item.selected_addons : [])"
                                            :key="`addon-${index}`"
                                        >
                                            إضافة: {{ addon.name }}
                                        </p>
                                    </div>
                                    <p v-if="item.notes" class="text-[11px] text-amber-600 font-bold">
                                        ملاحظة: {{ item.notes }}
                                    </p>
                                </div>
                            </div>
                            <span class="font-bold text-stone-800 dark:text-stone-200 text-sm">
                                {{ item.total_price }} ج.م
                            </span>
                        </div>
                    </div>
                </div>

                <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs space-y-3">
                    <h2 class="text-base font-black text-stone-900 dark:text-white">
                        بيانات العميل والعنوان
                    </h2>
                    <div class="space-y-2 text-xs">
                        <p class="font-bold text-stone-900 dark:text-white flex items-center gap-2">
                            <User class="w-4 h-4 text-stone-400" />
                            <span>الاسم: {{ order.customer?.user?.name || 'عميل' }}</span>
                        </p>
                        <p v-if="order.customer?.user?.phone" class="flex items-center gap-2 text-stone-700 dark:text-stone-300">
                            <Phone class="w-4 h-4 text-stone-400" />
                            <a :href="`tel:${order.customer.user.phone}`" class="hover:underline font-mono">
                                {{ order.customer.user.phone }}
                            </a>
                        </p>
                        <p class="flex items-start gap-2 text-stone-700 dark:text-stone-300">
                            <MapPin class="w-4 h-4 text-orange-500 shrink-0 mt-0.5" />
                            <span>{{ order.address }}</span>
                        </p>
                        <p v-if="order.customer_notes" class="text-amber-600 italic">
                            ملاحظة العميل: {{ order.customer_notes }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs space-y-4">
                    <h2 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-2">
                        <Bike class="w-4 h-4 text-emerald-600" />
                        <span>كابتن التوصيل</span>
                    </h2>

                    <div
                        v-if="driver"
                        class="p-4 rounded-2xl bg-emerald-50 dark:bg-stone-800 border border-emerald-200 dark:border-stone-700 text-xs space-y-2"
                    >
                        <p class="text-[10px] text-emerald-700 dark:text-emerald-400 font-bold uppercase">الطيار المعين</p>
                        <p class="font-bold text-sm text-stone-900 dark:text-white">{{ driver.name }}</p>
                        <p class="font-mono text-stone-500 font-bold">{{ driver.phone }}</p>
                        <div v-if="driver.phone" class="flex items-center gap-2 pt-1">
                            <a
                                :href="`tel:${driver.phone}`"
                                class="flex-1 py-1.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold text-center transition"
                            >
                                اتصال بالطيار
                            </a>
                            <a
                                :href="`https://wa.me/2${driver.phone.replace(/^0/, '')}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="py-1.5 px-3 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 text-xs font-bold transition"
                            >
                                واتساب
                            </a>
                        </div>
                    </div>
                    <form v-else class="space-y-3" @submit.prevent="handleAssignDriver">
                        <label class="block text-xs font-bold text-stone-600 dark:text-stone-400">
                            إسناد الطلب لطيار متاح
                        </label>
                        <select
                            v-model="selectedDriverId"
                            class="w-full p-2.5 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white"
                        >
                            <option value="">-- اختر كابتن متاح --</option>
                            <option v-for="d in available_drivers" :key="d.id" :value="d.id">
                                {{ d.name }} ({{ d.phone }})
                            </option>
                        </select>
                        <button
                            type="submit"
                            :disabled="!selectedDriverId"
                            class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow transition disabled:opacity-50"
                        >
                            إسناد وتكليف الطيار
                        </button>
                    </form>
                </div>

                <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs space-y-3 text-xs">
                    <h2 class="text-base font-black text-stone-900 dark:text-white pb-2 border-b border-stone-100 dark:border-stone-800">
                        الحساب المالي للطلب
                    </h2>

                    <div class="flex items-center justify-between text-stone-500">
                        <span>قيمة المنيو (المطبخ)</span>
                        <span class="font-bold text-stone-900 dark:text-white">{{ order.subtotal }} ج.م</span>
                    </div>
                    <div class="flex items-center justify-between text-stone-500">
                        <span>رسوم التوصيل</span>
                        <span class="font-bold text-stone-900 dark:text-white">{{ order.delivery_fee }} ج.م</span>
                    </div>
                    <div class="pt-2 border-t border-stone-100 dark:border-stone-800 flex items-center justify-between text-sm font-black text-stone-900 dark:text-white">
                        <span>إجمالي الطلب الكلي</span>
                        <span class="text-xl text-orange-600">{{ order.total_amount }} ج.م</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
