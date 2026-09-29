<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import GuestLayout from '../../Layouts/GuestLayout.vue';
import type { Customer, CustomerAddress } from '../../Types';
import { useCartStore } from '../../Stores/cartStore';
import { BORG_EL_ARAB_UNIVERSITIES, calculateDistanceKm } from '../../constants/universities';
import LocationPickerMap from '../../Components/LocationPickerMap.vue';
import {
    MapPin,
    Bike,
    CreditCard,
    ShieldCheck,
    Plus,
    ArrowRight,
    GraduationCap,
    AlertCircle,
    FileText,
    Navigation,
    CheckCircle2,
    Building2,
    Store,
} from '@lucide/vue';

const props = withDefaults(
    defineProps<{
        customer: Customer;
        addresses?: CustomerAddress[];
    }>(),
    {
        addresses: () => [],
    },
);

const cart = useCartStore();
const isVerifiedStudent = computed(() => props.customer?.student_status === 'APPROVED');

watch(
    [isVerifiedStudent, () => cart.restaurant],
    () => {
        if (isVerifiedStudent.value && cart.restaurant) {
            cart.setStudentDiscount(Number(cart.restaurant.student_discount_percentage) || 0);
        }
    },
    { immediate: true },
);

const addressMode = ref<'university' | 'saved' | 'custom' | 'gps'>(
    isVerifiedStudent.value ? 'university' : props.addresses.length > 0 ? 'saved' : 'university',
);

const selectedUniId = ref('BATU');
const selectedLocation = ref(BORG_EL_ARAB_UNIVERSITIES[0].locations[0]);
const roomDetails = ref('');

const defaultAddr =
    props.addresses.find((a) => a.is_default)?.address || props.addresses[0]?.address || '';
const selectedSavedAddress = ref(defaultAddr);
const customAddress = ref('');
const gpsAddress = ref('');
const gpsCoords = ref<{ lat: number; lng: number } | null>(null);
const roadQuote = ref<{ distance_km: number; duration_minutes: number; delivery_fee: number } | null>(null);
const notes = ref('');
const isSubmitting = ref(false);
const errorMsg = ref('');

const activeUni = computed(
    () => BORG_EL_ARAB_UNIVERSITIES.find((u) => u.id === selectedUniId.value) || BORG_EL_ARAB_UNIVERSITIES[0],
);

const customerCoords = computed(() => {
    if (addressMode.value === 'university') {
        return gpsCoords.value || { lat: activeUni.value.latitude, lng: activeUni.value.longitude };
    }
    return gpsCoords.value || (addressMode.value === 'saved' ? null : { lat: 30.8752, lng: 29.5841 });
});

const restLat = computed(() => Number(cart.restaurant?.latitude) || 30.87);
const restLng = computed(() => Number(cart.restaurant?.longitude) || 29.58);

const distanceKm = computed(() =>
    customerCoords.value
        ? calculateDistanceKm(restLat.value, restLng.value, customerCoords.value.lat, customerCoords.value.lng)
        : null,
);

const isPickupOnly = computed(() => cart.restaurant?.delivery_provider === 'PICKUP');

const feePerKm = computed(() => Number(cart.restaurant?.delivery_fee_per_km) || 0);
const baseFee = computed(() => Number(cart.restaurant?.delivery_base_fee ?? cart.restaurant?.delivery_fee ?? 10));

const calculatedDeliveryFee = computed(() => {
    if (isPickupOnly.value) {
        return 0;
    }

    return feePerKm.value > 0 && distanceKm.value !== null
        ? Math.round(baseFee.value + distanceKm.value * feePerKm.value)
        : Number(cart.restaurant?.delivery_fee || 15);
});

watch(
    [() => cart.restaurant?.id, () => customerCoords.value?.lat, () => customerCoords.value?.lng, isPickupOnly],
    () => {
        if (isPickupOnly.value || !cart.restaurant || !customerCoords.value) {
            roadQuote.value = null;
            return;
        }
        const controller = new AbortController();
        fetch('/delivery-quote', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                restaurant_id: cart.restaurant.id,
                latitude: customerCoords.value.lat,
                longitude: customerCoords.value.lng,
            }),
            signal: controller.signal,
        })
            .then((r) => (r.ok ? r.json() : Promise.reject()))
            .then((data) => {
                roadQuote.value = data;
            })
            .catch(() => {
                if (!controller.signal.aborted) {
                    roadQuote.value = null;
                }
            });
        return () => controller.abort();
    },
    { immediate: true },
);

const subtotal = computed(() => cart.getSubtotal);
const studentDiscount = computed(() => cart.getStudentDiscountAmount);
const deliveryFee = computed(() =>
    isPickupOnly.value ? 0 : (roadQuote.value?.delivery_fee ?? calculatedDeliveryFee.value),
);
const total = computed(() => Math.max(0, subtotal.value - studentDiscount.value + deliveryFee.value));

const handleUniChange = (uniId: string): void => {
    selectedUniId.value = uniId;
    const uni = BORG_EL_ARAB_UNIVERSITIES.find((u) => u.id === uniId);
    if (uni && uni.locations.length > 0) {
        selectedLocation.value = uni.locations[0];
    }
};

const handleMapLocationSelect = ({
    lat,
    lng,
    address,
}: {
    lat: number;
    lng: number;
    address: string;
    distanceKm?: number;
}): void => {
    gpsCoords.value = { lat, lng };
    gpsAddress.value = address;
};

const buildFinalAddress = (): string => {
    if (addressMode.value === 'university') {
        const extra = roomDetails.value.trim() ? ` — (${roomDetails.value.trim()})` : '';
        return `${activeUni.value.name} — ${selectedLocation.value}${extra}`;
    }
    if (addressMode.value === 'saved') {
        return selectedSavedAddress.value;
    }
    if (addressMode.value === 'gps') {
        return (
            gpsAddress.value ||
            (gpsCoords.value ? `موقع GPS: ${gpsCoords.value.lat.toFixed(6)},${gpsCoords.value.lng.toFixed(6)}` : '')
        );
    }
    return customAddress.value;
};

const handlePlaceOrder = (): void => {
    errorMsg.value = '';

    if (!cart.restaurant || cart.items.length === 0) {
        errorMsg.value = 'سلة الطلبات فارغة!';
        return;
    }

    const finalAddress = isPickupOnly.value
        ? `استلام من المطعم — ${cart.restaurant.address || cart.restaurant.name}`
        : buildFinalAddress();

    if (!isPickupOnly.value && !finalAddress.trim()) {
        errorMsg.value = 'الرجاء تحديد مكان استلام الوجبة أو إدخال العنوان.';
        return;
    }

    isSubmitting.value = true;

    const payload = {
        restaurant_id: cart.restaurant.id,
        items: cart.items.map((item) => ({
            menu_item_id: item.menuItem.id,
            quantity: item.quantity,
            options: item.selectedOptions,
            addons: item.selectedAddons,
            notes: item.notes || null,
        })),
        address: finalAddress,
        latitude: isPickupOnly.value ? (cart.restaurant.latitude ?? null) : (customerCoords.value?.lat ?? null),
        longitude: isPickupOnly.value ? (cart.restaurant.longitude ?? null) : (customerCoords.value?.lng ?? null),
        delivery_fee: deliveryFee.value,
        payment_method: 'CASH_ON_DELIVERY',
        customer_notes: notes.value || null,
    };

    router.post('/checkout', payload, {
        onSuccess: () => {
            cart.clearCart();
        },
        onError: (errs) => {
            isSubmitting.value = false;
            const first = Object.values(errs)[0];
            errorMsg.value = first ? String(first) : 'حدث خطأ أثناء تنفيذ الطلب.';
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <GuestLayout>
        <template v-if="cart.items.length === 0 || !cart.restaurant">
            <div class="max-w-xl mx-auto px-4 py-20 text-center space-y-4">
                <div class="w-20 h-20 rounded-3xl bg-orange-100 dark:bg-orange-950/40 text-orange-600 flex items-center justify-center mx-auto shadow-inner">
                    <AlertCircle class="w-10 h-10" />
                </div>
                <h2 class="text-2xl font-black text-stone-900 dark:text-white">لا توجد طلبات لإتمامها</h2>
                <p class="text-xs text-stone-500 max-w-sm mx-auto">الرجاء إضافة أصناف إلى سلتك أولاً من مطاعم برج العرب.</p>
                <Link
                    href="/restaurants"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-orange-600 text-white font-bold text-xs shadow-md shadow-orange-600/25 transition"
                >
                    تصفح المطاعم
                </Link>
            </div>
        </template>

        <template v-else>
            <Head title="إتمام الطلب والدفع" />

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="mb-8">
                    <h1 class="text-2xl sm:text-3xl font-black text-stone-900 dark:text-white mb-2">
                        إتمام وتأكيد الطلب
                    </h1>
                    <p class="text-xs sm:text-sm text-stone-500 dark:text-stone-400">
                        <template v-if="isPickupOnly">
                            طلب وجبتك من مطعم <strong class="text-orange-600 dark:text-orange-400">{{ cart.restaurant.name }}</strong> للاستلام من الفرع
                        </template>
                        <template v-else>
                            طلب وجبتك من مطعم <strong class="text-orange-600 dark:text-orange-400">{{ cart.restaurant.name }}</strong> وتحديد مكان الاستلام في برج العرب
                        </template>
                    </p>
                </div>

                <div
                    v-if="errorMsg"
                    class="mb-6 p-4 rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900 text-red-700 dark:text-red-300 text-xs font-bold animate-fade-in flex items-center gap-2"
                >
                    <AlertCircle class="w-4 h-4 shrink-0 text-red-500" />
                    <span>{{ errorMsg }}</span>
                </div>

                <form class="grid grid-cols-1 lg:grid-cols-3 gap-8" @submit.prevent="handlePlaceOrder">
                    <div class="lg:col-span-2 space-y-6">
                        <div
                            v-if="isPickupOnly"
                            class="p-6 sm:p-7 rounded-3xl bg-white dark:bg-stone-900 border border-emerald-200 dark:border-emerald-900 shadow-xs space-y-3"
                        >
                            <h2 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-2">
                                <Store class="w-5 h-5 text-emerald-500" />
                                <span>استلام من المطعم</span>
                            </h2>
                            <p class="text-xs text-stone-500 leading-relaxed">
                                هتستلم طلبك بنفسك من فرع المطعم. مفيش رسوم توصيل.
                            </p>
                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/40 px-4 py-3 text-xs font-bold text-emerald-800 dark:text-emerald-200">
                                {{ cart.restaurant.address || cart.restaurant.name }}
                            </div>
                        </div>

                        <div
                            v-else
                            class="p-6 sm:p-7 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs space-y-5"
                        >
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-stone-100 dark:border-stone-800">
                                <h2 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-2">
                                    <MapPin class="w-5 h-5 text-orange-500" />
                                    <span>مكان استلام الطلب في برج العرب</span>
                                </h2>

                                <span
                                    v-if="isVerifiedStudent"
                                    class="inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                                >
                                    <GraduationCap class="w-3.5 h-3.5" />
                                    <span>طالب موثق (الخصم مطبق)</span>
                                </span>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 bg-stone-100 dark:bg-stone-800 p-1.5 rounded-2xl text-xs font-black">
                                <button
                                    type="button"
                                    class="py-2 px-3 rounded-xl transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                                    :class="addressMode === 'university' ? 'bg-orange-600 text-white shadow-xs' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-white'"
                                    @click="addressMode = 'university'"
                                >
                                    <GraduationCap class="w-4 h-4" />
                                    <span>مقر جامعي 🎓</span>
                                </button>

                                <button
                                    v-if="addresses.length > 0"
                                    type="button"
                                    class="py-2 px-3 rounded-xl transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                                    :class="addressMode === 'saved' ? 'bg-orange-600 text-white shadow-xs' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-white'"
                                    @click="addressMode = 'saved'"
                                >
                                    <Building2 class="w-4 h-4" />
                                    <span>عناوين محفوظة</span>
                                </button>

                                <button
                                    type="button"
                                    class="py-2 px-3 rounded-xl transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                                    :class="addressMode === 'gps' ? 'bg-orange-600 text-white shadow-xs' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-white'"
                                    @click="addressMode = 'gps'"
                                >
                                    <MapPin class="w-4 h-4" />
                                    <span>خريطة ودبوس GPS 📍</span>
                                </button>

                                <button
                                    type="button"
                                    class="py-2 px-3 rounded-xl transition-all flex items-center justify-center gap-1.5 cursor-pointer"
                                    :class="addressMode === 'custom' ? 'bg-orange-600 text-white shadow-xs' : 'text-stone-600 dark:text-stone-400 hover:text-stone-900 dark:hover:text-white'"
                                    @click="addressMode = 'custom'"
                                >
                                    <Plus class="w-4 h-4" />
                                    <span>عنوان مخصص</span>
                                </button>
                            </div>

                            <div v-if="addressMode === 'university'" class="space-y-4 pt-1 animate-fade-in">
                                <div>
                                    <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-2">
                                        اختر جامعتك في برج العرب:
                                    </label>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <button
                                            v-for="uni in BORG_EL_ARAB_UNIVERSITIES"
                                            :key="uni.id"
                                            type="button"
                                            class="p-3.5 rounded-2xl border text-right transition-all flex flex-col justify-between cursor-pointer"
                                            :class="
                                                selectedUniId === uni.id
                                                    ? 'border-orange-500 bg-orange-50/70 dark:bg-orange-950/40 ring-2 ring-orange-500/20 shadow-xs'
                                                    : 'border-stone-200 dark:border-stone-800 hover:border-stone-300 bg-stone-50/50 dark:bg-stone-800/40'
                                            "
                                            @click="handleUniChange(uni.id)"
                                        >
                                            <div class="flex items-center justify-between mb-2">
                                                <span
                                                    class="text-[10px] font-black px-2 py-0.5 rounded-md"
                                                    :class="
                                                        selectedUniId === uni.id
                                                            ? 'bg-orange-600 text-white'
                                                            : 'bg-stone-200 dark:bg-stone-700 text-stone-700 dark:text-stone-300'
                                                    "
                                                >
                                                    {{ uni.badge }}
                                                </span>
                                                <CheckCircle2 v-if="selectedUniId === uni.id" class="w-4 h-4 text-orange-600" />
                                            </div>
                                            <h4 class="font-bold text-xs text-stone-900 dark:text-white leading-snug">{{ uni.name }}</h4>
                                            <p class="text-[10px] text-stone-500 mt-1">{{ uni.description }}</p>
                                        </button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1">
                                            المبنى أو نقطة الاستلام داخل {{ activeUni.shortName }}:
                                        </label>
                                        <select
                                            v-model="selectedLocation"
                                            class="w-full p-3 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500 font-medium"
                                        >
                                            <option v-for="(loc, idx) in activeUni.locations" :key="idx" :value="loc">{{ loc }}</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-stone-700 dark:text-stone-300 mb-1">
                                            تفاصيل إضافية (الدور، القاعة، رقم الغرفة بالسكن):
                                        </label>
                                        <input
                                            v-model="roomDetails"
                                            type="text"
                                            placeholder="مثال: الدور الثاني - معمل 102 أو غرفة 204..."
                                            class="w-full p-3 text-xs rounded-xl bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 text-stone-900 dark:text-white focus:outline-none focus:border-orange-500"
                                        />
                                    </div>
                                </div>

                                <div class="p-3.5 rounded-2xl bg-orange-50/50 dark:bg-stone-800/80 border border-orange-200/80 dark:border-stone-700 text-xs flex items-center gap-2">
                                    <MapPin class="w-4 h-4 text-orange-600 shrink-0" />
                                    <div class="min-w-0">
                                        <span class="font-bold text-stone-900 dark:text-white block">العنوان المحدد للطيار:</span>
                                        <span class="text-stone-600 dark:text-stone-300 truncate block">
                                            {{ activeUni.name }} — {{ selectedLocation }} {{ roomDetails ? `(${roomDetails})` : '' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="addressMode === 'saved' && addresses.length > 0" class="space-y-3 animate-fade-in">
                                <label
                                    v-for="addr in addresses"
                                    :key="addr.id"
                                    class="flex items-start justify-between p-4 rounded-2xl border text-xs cursor-pointer transition"
                                    :class="
                                        selectedSavedAddress === addr.address
                                            ? 'border-orange-500 bg-orange-50/50 dark:bg-orange-950/30 text-stone-900 dark:text-white ring-1 ring-orange-500'
                                            : 'border-stone-200 dark:border-stone-800 text-stone-600 dark:text-stone-400 hover:bg-stone-50 dark:hover:bg-stone-800'
                                    "
                                >
                                    <div class="flex items-start gap-3">
                                        <input
                                            v-model="selectedSavedAddress"
                                            type="radio"
                                            name="address_choice"
                                            :value="addr.address"
                                            class="mt-0.5 text-orange-600 focus:ring-orange-500"
                                        />
                                        <div>
                                            <span class="font-bold block text-stone-900 dark:text-white">{{ addr.label }}</span>
                                            <span class="text-xs text-stone-500 dark:text-stone-400 mt-0.5 block">{{ addr.address }}</span>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <div v-if="addressMode === 'gps'" class="space-y-3 animate-fade-in">
                                <p class="text-[11px] text-stone-500 dark:text-stone-400 flex items-center gap-1">
                                    <Navigation class="w-3.5 h-3.5 text-orange-500" />
                                    اسحب الدبوس أو اضغط على الخريطة لتحديد موقعك بدقة، أو اضغط زر GPS لتحديد موقعك الحالي تلقائياً.
                                </p>
                                <LocationPickerMap
                                    :restaurant-lat="restLat"
                                    :restaurant-lng="restLng"
                                    :restaurant-name="cart.restaurant.name"
                                    :initial-lat="gpsCoords?.lat"
                                    :initial-lng="gpsCoords?.lng"
                                    :auto-locate-on-mount="true"
                                    :on-location-select="handleMapLocationSelect"
                                />
                                <div
                                    v-if="gpsAddress"
                                    class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs flex items-start gap-2"
                                >
                                    <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                                    <div>
                                        <span class="font-bold text-stone-900 dark:text-white block">العنوان المحدد:</span>
                                        <p class="text-stone-600 dark:text-stone-300 leading-relaxed">{{ gpsAddress }}</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="addressMode === 'custom'" class="space-y-2 animate-fade-in">
                                <label class="block text-xs font-bold text-stone-700 dark:text-stone-300">
                                    العنوان بالتفصيل (الحي، الشارع، رقم العمارة، معالم قريبة):
                                </label>
                                <textarea
                                    v-model="customAddress"
                                    required
                                    rows="3"
                                    placeholder="مثال: برج العرب الجديدة، الحي السكني الثاني، عمارة 15، الدور الثالث..."
                                    class="w-full bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-2xl p-3 text-xs text-stone-900 dark:text-white placeholder-stone-400 focus:outline-none focus:border-orange-500"
                                />
                            </div>
                        </div>

                        <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs space-y-3">
                            <h2 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-2">
                                <FileText class="w-5 h-5 text-orange-500" />
                                <span>ملاحظات خاصة {{ isPickupOnly ? 'للمطعم' : 'لكابتن التوصيل أو المطعم' }} (اختياري)</span>
                            </h2>
                            <textarea
                                v-model="notes"
                                rows="2"
                                placeholder="مثال: رن الجرس مرة واحدة، أو اتصل بي هاتفياً عند الوصول أمام البوابة..."
                                class="w-full bg-stone-50 dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-2xl p-3 text-xs text-stone-900 dark:text-white placeholder-stone-400 focus:outline-none focus:border-orange-500"
                            />
                        </div>

                        <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs space-y-4">
                            <h2 class="text-base font-black text-stone-900 dark:text-white flex items-center gap-2">
                                <CreditCard class="w-5 h-5 text-orange-500" />
                                <span>طريقة الدفع</span>
                            </h2>

                            <div class="p-4 rounded-2xl border-2 border-orange-500 bg-orange-50/40 dark:bg-orange-950/20 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-orange-600 text-white flex items-center justify-center font-bold text-xs shadow-sm">
                                        كاش
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-xs text-stone-900 dark:text-white">الدفع نقداً عند الاستلام (COD)</h3>
                                        <p class="text-[11px] text-stone-500">ادفع المبلغ للكابتن يد بيد عند استلام الوجبة ساخنة</p>
                                    </div>
                                </div>
                                <ShieldCheck class="w-5 h-5 text-emerald-500" />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="p-6 rounded-3xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-sm space-y-4">
                            <h2 class="text-base font-black text-stone-900 dark:text-white pb-3 border-b border-stone-100 dark:border-stone-800">
                                ملخص الفاتورة
                            </h2>

                            <div class="space-y-2.5 max-h-56 overflow-y-auto custom-scrollbar pr-1 divide-y divide-stone-50 dark:divide-stone-800/40">
                                <div v-for="item in cart.items" :key="item.id" class="pt-2 first:pt-0">
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="text-stone-800 dark:text-stone-200 font-bold">
                                            {{ item.quantity }} × {{ item.menuItem.name }}
                                        </span>
                                        <span class="font-black text-stone-900 dark:text-white">
                                            {{ item.totalPrice.toFixed(1) }} ج.م
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-stone-100 dark:border-stone-800 space-y-2.5 text-xs">
                                <div class="flex items-center justify-between text-stone-500">
                                    <span>المجموع الفرعي</span>
                                    <span class="font-bold text-stone-800 dark:text-stone-200">{{ subtotal.toFixed(2) }} ج.م</span>
                                </div>

                                <div
                                    v-if="studentDiscount > 0"
                                    class="flex items-center justify-between text-emerald-600 dark:text-emerald-400 font-bold bg-emerald-50 dark:bg-emerald-950/40 p-2 rounded-xl border border-emerald-200/50 dark:border-emerald-800"
                                >
                                    <span class="flex items-center gap-1">
                                        <GraduationCap class="w-3.5 h-3.5" />
                                        خصم الطلاب الجامعي
                                    </span>
                                    <span>-{{ studentDiscount.toFixed(2) }} ج.م</span>
                                </div>

                                <div class="flex items-center justify-between text-stone-500">
                                    <span>{{ isPickupOnly ? 'الاستلام من المطعم' : 'رسوم التوصيل' }}</span>
                                    <span class="font-bold text-stone-800 dark:text-stone-200">
                                        {{ isPickupOnly ? 'بدون رسوم' : `${deliveryFee.toFixed(2)} ج.م` }}
                                    </span>
                                </div>

                                <div
                                    v-if="!isPickupOnly && distanceKm !== null"
                                    class="flex items-center justify-between text-[11px] p-2 rounded-xl bg-orange-500/10 text-orange-600 dark:text-orange-400 font-bold border border-orange-500/20"
                                >
                                    <span class="flex items-center gap-1">
                                        <Bike class="w-3.5 h-3.5" />
                                        المسافة المقدرة للموقع:
                                    </span>
                                    <span>
                                        {{
                                            roadQuote
                                                ? `${roadQuote.distance_km} كم طريق فعلي • حوالي ${roadQuote.duration_minutes} دقيقة`
                                                : `${distanceKm} كم (جارٍ حساب مسار الطريق...)`
                                        }}
                                    </span>
                                </div>

                                <div class="pt-3 border-t border-stone-100 dark:border-stone-800 flex items-center justify-between text-base font-black text-stone-900 dark:text-white">
                                    <span>المبلغ الإجمالي</span>
                                    <span class="text-2xl text-orange-600 dark:text-orange-400">{{ total.toFixed(2) }} ج.م</span>
                                </div>
                            </div>

                            <button
                                type="submit"
                                :disabled="isSubmitting"
                                class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 text-white font-black text-sm shadow-xl shadow-orange-600/30 flex items-center justify-center gap-2 transition disabled:opacity-60 cursor-pointer"
                            >
                                <span>{{ isSubmitting ? 'جارٍ إرسال الطلب...' : 'تأكيد وإرسال الطلب الآن' }}</span>
                                <ArrowRight class="w-4 h-4 rotate-180" />
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </template>
    </GuestLayout>
</template>
