<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import type { AvailabilityStatus, Restaurant } from '../../../Types';
import RestaurantLocationMap from '../../../Components/RestaurantLocationMap.vue';
import { Clock, MapPin, Bike, ImagePlus, Upload } from '@lucide/vue';
import { availabilityMeta, resolveAvailability } from '../../../lib/restaurantAvailability';
import { resolveMediaUrl } from '../../../lib/media';

const props = defineProps<{
    restaurant: Restaurant;
}>();

function toTimeInput(value?: string | null): string {
    if (!value) {
        return '08:00';
    }
    return value.slice(0, 5);
}

const availabilityOptions: {
    value: AvailabilityStatus;
    label: string;
    hint: string;
    activeClass: string;
    idleClass: string;
}[] = [
    { value: 'OPEN', label: 'مفتوح', hint: 'يستقبل الطلبات فوراً', activeClass: 'border-emerald-500 bg-emerald-50 text-emerald-800', idleClass: 'border-stone-200 bg-white text-stone-600' },
    { value: 'BUSY', label: 'مشغول', hint: 'يستقبل مع تأخير متوقع', activeClass: 'border-amber-500 bg-amber-50 text-amber-800', idleClass: 'border-stone-200 bg-white text-stone-600' },
    { value: 'CLOSED', label: 'مغلق', hint: 'مش بياخد طلبات', activeClass: 'border-stone-700 bg-stone-100 text-stone-800', idleClass: 'border-stone-200 bg-white text-stone-600' },
];

const updating = ref(false);
const coverFileInput = ref<HTMLInputElement | null>(null);
const logoFileInput = ref<HTMLInputElement | null>(null);
const coverPreview = ref<string | null>(null);
const logoPreview = ref<string | null>(null);

const availability = computed(() => resolveAvailability(props.restaurant));
const meta = computed(() => availabilityMeta(availability.value));

const managesOwnDelivery = computed(() => props.restaurant.delivery_provider === 'RESTAURANT');
const isPickupOnly = computed(() => props.restaurant.delivery_provider === 'PICKUP');
const isPlatformDelivery = computed(() => props.restaurant.delivery_provider === 'PLATFORM');

const currentCoverUrl = computed(() =>
    coverPreview.value || resolveMediaUrl(props.restaurant.cover_image || props.restaurant.logo),
);
const currentLogoUrl = computed(() =>
    logoPreview.value || resolveMediaUrl(props.restaurant.logo || props.restaurant.cover_image),
    
);

const form = useForm<{
    name: string;
    description: string;
    phone: string;
    whatsapp: string;
    email: string;
    address: string;
    opening_time: string;
    closing_time: string;
    minimum_order_amount: number;
    estimated_delivery_time: number;
    delivery_fee: number;
    delivery_base_fee: number;
    delivery_fee_per_km: number;
    delivery_enabled: boolean;
    latitude: number | string;
    longitude: number | string;
    logo: File | null;
    cover_image: File | null;
}>({
    name: props.restaurant.name || '',
    description: props.restaurant.description || '',
    phone: props.restaurant.phone || '',
    whatsapp: props.restaurant.whatsapp || '',
    email: props.restaurant.email || '',
    address: props.restaurant.address || '',
    opening_time: toTimeInput(props.restaurant.opening_time),
    closing_time: toTimeInput(props.restaurant.closing_time),
    minimum_order_amount: props.restaurant.minimum_order_amount || 0,
    estimated_delivery_time: props.restaurant.estimated_delivery_time || 35,
    delivery_fee: props.restaurant.delivery_fee || 0,
    delivery_base_fee: props.restaurant.delivery_base_fee ?? (props.restaurant.delivery_fee || 10),
    delivery_fee_per_km: props.restaurant.delivery_fee_per_km ?? 5,
    delivery_enabled: props.restaurant.delivery_enabled !== false,
    latitude: props.restaurant.latitude || '',
    longitude: props.restaurant.longitude || '',
    logo: null,
    cover_image: null,
});

const handleMapLocationChange = (lat: number, lng: number, address: string): void => {
    form.latitude = lat;
    form.longitude = lng;
    form.address = address;
};

const onCoverChange = (event: Event): void => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) {
        return;
    }

    form.cover_image = file;
    coverPreview.value = URL.createObjectURL(file);
};

const onLogoChange = (event: Event): void => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) {
        return;
    }

    form.logo = file;
    logoPreview.value = URL.createObjectURL(file);
};

const handleSubmit = (): void => {
    form.post('/restaurant/settings', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.logo = null;
            form.cover_image = null;
            coverPreview.value = null;
            logoPreview.value = null;
        },
    });
};

const setAvailability = (status: AvailabilityStatus): void => {
    if (updating.value || props.restaurant.status !== 'ACTIVE' || status === availability.value) {
        return;
    }
    updating.value = true;
    router.post(
        '/restaurant/settings/availability',
        { availability_status: status },
        {
            preserveScroll: true,
            onFinish: () => {
                updating.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="إعدادات المطعم — بوابة المطعم" />

    <div class="max-w-4xl space-y-6">
        <div class="rounded-3xl border border-stone-200 bg-white p-5 shadow-xs">
            <div class="mb-4">
                <p class="font-bold text-orange-600">حالة المطعم للعملاء</p>
                <h2 class="mt-1 text-lg font-black text-stone-900">الحالة الحالية: {{ meta.label }}</h2>
                <p class="mt-1 text-xs text-stone-500">اختَر الحالة وهتحدث عند العميل فوراً.</p>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <button
                    v-for="option in availabilityOptions"
                    :key="option.value"
                    type="button"
                    :disabled="updating || restaurant.status !== 'ACTIVE'"
                    :class="[
                        'rounded-2xl border-2 px-4 py-4 text-right transition disabled:opacity-50',
                        availability === option.value ? option.activeClass : option.idleClass,
                    ]"
                    @click="setAvailability(option.value)"
                >
                    <p class="text-sm font-black">{{ option.label }}</p>
                    <p class="mt-1 text-[11px] opacity-80">{{ option.hint }}</p>
                </button>
            </div>
        </div>

        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-xs dark:border-stone-800 dark:bg-stone-900">
            <h1 class="mb-1 text-xl font-black text-stone-900 dark:text-white">إعدادات المطعم والفرع</h1>
            <p class="mb-6 text-xs text-stone-400">حدّد مواعيد العمل ورسوم التوصيل من هنا — دي بتاعة المطعم مش الإدارة.</p>

            <form class="space-y-6" @submit.prevent="handleSubmit">
                <div class="rounded-2xl border border-orange-200 bg-orange-50/50 p-4 dark:border-orange-900/40 dark:bg-orange-950/20">
                    <h3 class="mb-1 flex items-center gap-1.5 text-sm font-black text-stone-900 dark:text-white">
                        <ImagePlus class="h-4 w-4 text-orange-500" />
                        صور المطعم للعملاء
                    </h3>
                    <p class="mb-4 text-[11px] text-stone-500">
                        صورة الغلاف هي اللي بتظهر في كارت المطعم عند العميل. الشعار بيظهر كدائرة صغيرة فوق الصورة.
                    </p>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-xs font-bold">صورة الغلاف (تظهر للعميل)</label>
                            <input
                                ref="coverFileInput"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="hidden"
                                @change="onCoverChange"
                            />
                            <div
                                class="group relative h-40 cursor-pointer overflow-hidden rounded-2xl border-2 border-dashed border-orange-300 bg-white dark:border-stone-700 dark:bg-stone-900"
                                @click="coverFileInput?.click()"
                            >
                                <img
                                    :src="currentCoverUrl"
                                    alt="صورة الغلاف"
                                    class="h-full w-full object-cover"
                                />
                                <div class="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-black/45 opacity-0 transition group-hover:opacity-100">
                                    <Upload class="h-5 w-5 text-white" />
                                    <span class="text-xs font-black text-white">تغيير صورة الغلاف</span>
                                </div>
                            </div>
                            <p v-if="form.errors.cover_image" class="mt-1 text-[11px] text-red-500">{{ form.errors.cover_image }}</p>
                            <p class="mt-1 text-[10px] text-stone-400">PNG, JPG, WEBP — حتى 5 ميجابايت</p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-bold">شعار المطعم</label>
                            <input
                                ref="logoFileInput"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                class="hidden"
                                @change="onLogoChange"
                            />
                            <div
                                class="group relative mx-auto aspect-square w-full max-w-[140px] cursor-pointer overflow-hidden rounded-2xl border-2 border-dashed border-orange-300 bg-white dark:border-stone-700 dark:bg-stone-900"
                                @click="logoFileInput?.click()"
                            >
                                <img
                                    :src="currentLogoUrl"
                                    alt="شعار المطعم"
                                    class="h-full w-full object-cover"
                                />
                                <div class="absolute inset-0 flex flex-col items-center justify-center gap-1 bg-black/45 opacity-0 transition group-hover:opacity-100">
                                    <Upload class="h-4 w-4 text-white" />
                                    <span class="text-[10px] font-black text-white">تغيير الشعار</span>
                                </div>
                            </div>
                            <p v-if="form.errors.logo" class="mt-1 text-[11px] text-red-500">{{ form.errors.logo }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-bold">اسم المطعم</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full rounded-xl border border-stone-200 bg-stone-50 p-2.5 text-xs dark:border-stone-700 dark:bg-stone-800"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-[11px] text-red-500">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold">رقم الهاتف</label>
                        <input
                            v-model="form.phone"
                            type="text"
                            class="w-full rounded-xl border border-stone-200 bg-stone-50 p-2.5 text-xs dark:border-stone-700 dark:bg-stone-800"
                        />
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold">وصف المطعم</label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        class="w-full rounded-xl border border-stone-200 bg-stone-50 p-2.5 text-xs dark:border-stone-700 dark:bg-stone-800"
                    />
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold">عنوان الفرع</label>
                    <input
                        v-model="form.address"
                        type="text"
                        class="w-full rounded-xl border border-stone-200 bg-stone-50 p-2.5 text-xs dark:border-stone-700 dark:bg-stone-800"
                    />
                </div>

                <div class="rounded-2xl border border-orange-200 bg-orange-50/60 p-4 dark:border-orange-900/40 dark:bg-orange-950/20">
                    <h3 class="mb-3 flex items-center gap-1.5 text-sm font-black text-stone-900 dark:text-white">
                        <Clock class="h-4 w-4 text-orange-500" />
                        أوقات العمل
                    </h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-bold">وقت الفتح</label>
                            <input
                                v-model="form.opening_time"
                                type="time"
                                class="w-full rounded-xl border border-stone-200 bg-white p-2.5 text-xs dark:border-stone-700 dark:bg-stone-800"
                            />
                            <p v-if="form.errors.opening_time" class="mt-1 text-[11px] text-red-500">{{ form.errors.opening_time }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold">وقت الإغلاق</label>
                            <input
                                v-model="form.closing_time"
                                type="time"
                                class="w-full rounded-xl border border-stone-200 bg-white p-2.5 text-xs dark:border-stone-700 dark:bg-stone-800"
                            />
                            <p v-if="form.errors.closing_time" class="mt-1 text-[11px] text-red-500">{{ form.errors.closing_time }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold">الحد الأدنى للطلب (ج.م)</label>
                            <input
                                v-model.number="form.minimum_order_amount"
                                type="number"
                                class="w-full rounded-xl border border-stone-200 bg-white p-2.5 text-xs dark:border-stone-700 dark:bg-stone-800"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold">متوسط التجهيز (دقيقة)</label>
                            <input
                                v-model.number="form.estimated_delivery_time"
                                type="number"
                                class="w-full rounded-xl border border-stone-200 bg-white p-2.5 text-xs dark:border-stone-700 dark:bg-stone-800"
                            />
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-stone-200 bg-stone-50/80 p-4 dark:border-stone-700 dark:bg-stone-800/40">
                    <h3 class="mb-3 flex items-center gap-1.5 text-sm font-black text-stone-900 dark:text-white">
                        <Bike class="h-4 w-4 text-orange-500" />
                        إعدادات التوصيل
                    </h3>

                    <div
                        v-if="isPickupOnly"
                        class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200"
                    >
                        المطعم يعمل بنظام الاستلام فقط — العملاء بيستلموا من الفرع ومفيش رسوم توصيل.
                    </div>

                    <div
                        v-else-if="isPlatformDelivery"
                        class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs text-sky-800 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-200"
                    >
                        التوصيل تديره المنصة — سعر التوصيل ثابت من الإدارة ومش تقدر تعدّله من هنا.
                        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3 text-[11px] font-bold text-sky-900 dark:text-sky-100">
                            <span>ثابت: {{ Number(restaurant.delivery_fee || 0).toFixed(2) }} ج.م</span>
                            <span>فتح العداد: {{ Number(restaurant.delivery_base_fee ?? restaurant.delivery_fee ?? 0).toFixed(2) }} ج.م</span>
                            <span>الكيلو: {{ Number(restaurant.delivery_fee_per_km ?? 0).toFixed(2) }} ج.م</span>
                        </div>
                    </div>

                    <template v-else-if="managesOwnDelivery">
                        <div class="mb-4 flex items-center justify-between gap-3 rounded-xl border border-stone-200 bg-white px-4 py-3 dark:border-stone-700 dark:bg-stone-900">
                            <div>
                                <p class="text-xs font-black text-stone-900 dark:text-white">التوصيل متاح للعملاء</p>
                                <p class="mt-0.5 text-[11px] text-stone-500">لو قفّلت التوصيل، العملاء مش هيقدروا يطلبوا توصيل.</p>
                            </div>
                            <button
                                type="button"
                                :class="[
                                    'relative h-6 w-11 rounded-full transition-colors',
                                    form.delivery_enabled ? 'bg-orange-500' : 'bg-stone-300 dark:bg-stone-600',
                                ]"
                                @click="form.delivery_enabled = !form.delivery_enabled"
                            >
                                <span
                                    :class="[
                                        'absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform',
                                        form.delivery_enabled ? 'translate-x-5' : 'translate-x-0.5',
                                    ]"
                                />
                            </button>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-xs font-bold">رسوم التوصيل الثابتة (ج.م)</label>
                                <input
                                    v-model.number="form.delivery_fee"
                                    type="number"
                                    step="0.5"
                                    class="w-full rounded-xl border border-stone-200 bg-white p-2.5 text-xs dark:border-stone-700 dark:bg-stone-900"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold">سعر فتح العداد (ج.م)</label>
                                <input
                                    v-model.number="form.delivery_base_fee"
                                    type="number"
                                    step="0.5"
                                    class="w-full rounded-xl border border-stone-200 bg-white p-2.5 text-xs dark:border-stone-700 dark:bg-stone-900"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-bold">سعر الكيلو (ج.م / كم)</label>
                                <input
                                    v-model.number="form.delivery_fee_per_km"
                                    type="number"
                                    step="0.5"
                                    class="w-full rounded-xl border border-stone-200 bg-white p-2.5 text-xs dark:border-stone-700 dark:bg-stone-900"
                                />
                            </div>
                        </div>
                    </template>
                </div>

                <div class="space-y-4 rounded-2xl border border-orange-500/20 bg-orange-500/5 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="flex items-center gap-1.5 text-sm font-bold text-stone-900 dark:text-white">
                                <MapPin class="h-4 w-4 text-orange-500" />
                                موقع المطعم على الخريطة
                            </h3>
                            <p class="mt-0.5 text-[11px] text-stone-500">حدّد موقعك بدقة لحساب التوصيل وتتبع الطيار</p>
                        </div>
                        <span
                            v-if="form.latitude && form.longitude"
                            class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                        >
                            ✓ {{ Number(form.latitude).toFixed(5) }}, {{ Number(form.longitude).toFixed(5) }}
                        </span>
                    </div>

                    <RestaurantLocationMap
                        :initial-lat="restaurant.latitude"
                        :initial-lng="restaurant.longitude"
                        :restaurant-name="restaurant.name"
                        :on-location-change="handleMapLocationChange"
                    />
                </div>

                <div class="flex justify-end border-t border-stone-100 pt-4 dark:border-stone-800">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-xl bg-orange-600 px-8 py-3 text-xs font-bold text-white shadow-md transition hover:bg-orange-700 disabled:opacity-60"
                    >
                        {{ form.processing ? 'جارٍ الحفظ...' : 'حفظ الإعدادات' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
