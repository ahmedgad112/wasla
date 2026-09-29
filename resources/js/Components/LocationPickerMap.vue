<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { MapPin, Navigation, Crosshair, Loader2, Check, Info } from '@lucide/vue';

const props = withDefaults(
    defineProps<{
        initialLat?: number | null;
        initialLng?: number | null;
        initialAddress?: string;
        onLocationSelect: (location: { lat: number; lng: number; address: string; distanceKm?: number }) => void;
        restaurantLat?: number;
        restaurantLng?: number;
        restaurantName?: string;
        autoLocateOnMount?: boolean;
    }>(),
    {
        initialAddress: '',
        restaurantName: 'المطعم',
        autoLocateOnMount: true,
    },
);

const BORG_EL_ARAB_LANDMARKS = [
    { name: 'جامعة برج العرب التكنولوجية (BATU)', lat: 30.8756, lng: 29.5842 },
    { name: 'الجامعة المصرية اليابانية (E-JUST)', lat: 30.8648, lng: 29.5741 },
    { name: 'جامعة الإسكندرية الأهلية', lat: 30.8805, lng: 29.5762 },
    { name: 'جامعة سنجور الدولية', lat: 30.8805, lng: 29.5912 },
    { name: 'سكن الطلاب والحي الأول', lat: 30.871, lng: 29.589 },
    { name: 'صينية الهوارية وموقف السيارات', lat: 30.892, lng: 29.601 },
    { name: 'نادي سموحة - برج العرب', lat: 30.858, lng: 29.593 },
];

const mapEl = ref<HTMLElement | null>(null);
const mapInstance = ref<L.Map | null>(null);
const customerMarker = ref<L.Marker | null>(null);
const initialLocationAttempted = ref(false);

const defaultLat = props.initialLat && !isNaN(Number(props.initialLat)) ? Number(props.initialLat) : 30.8752;
const defaultLng = props.initialLng && !isNaN(Number(props.initialLng)) ? Number(props.initialLng) : 29.5841;

const coords = ref({ lat: defaultLat, lng: defaultLng });
const addressText = ref(props.initialAddress || '');
const isLocating = ref(false);
const isReverseGeocoding = ref(false);
const hasDetectedGps = ref(false);

const calcDistance = (lat1: number, lon1: number, lat2: number, lon2: number): number => {
    const R = 6371;
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLon = ((lon2 - lon1) * Math.PI) / 180;
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos((lat1 * Math.PI) / 180) * Math.cos((lat2 * Math.PI) / 180) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return Math.round(R * c * 10) / 10;
};

const emitChange = (lat: number, lng: number, addr: string): void => {
    const dist =
        props.restaurantLat && props.restaurantLng
            ? calcDistance(props.restaurantLat, props.restaurantLng, lat, lng)
            : undefined;
    props.onLocationSelect({
        lat,
        lng,
        address: addr,
        distanceKm: dist,
    });
};

const fallbackAddress = (lat: number, lng: number): void => {
    const fallback = `موقع محدد في برج العرب (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
    addressText.value = fallback;
    emitChange(lat, lng, fallback);
};

const reverseGeocode = async (lat: number, lng: number): Promise<void> => {
    isReverseGeocoding.value = true;
    try {
        const res = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=ar`,
            { headers: { 'User-Agent': 'Fatrny-Shokrn-App' } },
        );
        if (res.ok) {
            const data = await res.json();
            const cleanName = data.display_name
                ? data.display_name.split(',').slice(0, 3).join('، ')
                : `برج العرب (${lat.toFixed(5)}, ${lng.toFixed(5)})`;
            addressText.value = cleanName;
            emitChange(lat, lng, cleanName);
        } else {
            fallbackAddress(lat, lng);
        }
    } catch {
        fallbackAddress(lat, lng);
    } finally {
        isReverseGeocoding.value = false;
    }
};

const updateLocation = (lat: number, lng: number, name?: string, openPopup = true): void => {
    coords.value = { lat, lng };
    if (customerMarker.value) {
        customerMarker.value.setLatLng([lat, lng]);
        if (openPopup) {
            customerMarker.value.openPopup();
        }
    }
    mapInstance.value?.setView([lat, lng], 16, { animate: true });
    if (name) {
        addressText.value = name;
        emitChange(lat, lng, name);
    } else {
        void reverseGeocode(lat, lng);
    }
};

const handleGPS = (): void => {
    if (!navigator.geolocation) {
        alert('خاصية تحديد الموقع الجغرافي غير مدعومة في متصفحك.');
        return;
    }

    isLocating.value = true;
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const { latitude, longitude } = pos.coords;
            hasDetectedGps.value = true;
            isLocating.value = false;
            updateLocation(latitude, longitude, undefined, true);
        },
        (err) => {
            isLocating.value = false;
            if (err.code === 1) {
                alert('يرجى السماح بصلاحية الموقع في المتصفح لتحديد مكانك تلقائياً بالدبوس.');
            } else {
                alert('تعذر قراءة إشارة الـ GPS. يمكنك سحب الدبوس أو النقر على الخريطة يدوياً.');
            }
        },
        { enableHighAccuracy: true, timeout: 10000 },
    );
};

onMounted(() => {
    if (!mapEl.value || mapInstance.value) {
        return;
    }

    const customerPinIcon = L.divIcon({
        className: 'uber-interactive-customer-pin',
        html: `
                <div style="position: relative; width: 46px; height: 56px; display: flex; flex-direction: column; align-items: center;">
                    <div style="width: 44px; height: 44px; background: linear-gradient(135deg, #ea580c, #f97316); border: 3px solid #ffffff; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); box-shadow: 0 6px 18px rgba(234,88,12,0.5); display: flex; align-items: center; justify-content: center; color: white;">
                        <div style="transform: rotate(45deg); display: flex; align-items: center; justify-content: center;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"/></svg>
                        </div>
                    </div>
                    <div style="width: 12px; height: 6px; background: rgba(0,0,0,0.3); border-radius: 50%; margin-top: 4px; filter: blur(1px);"></div>
                </div>
            `,
        iconSize: [46, 56],
        iconAnchor: [23, 52],
        popupAnchor: [0, -50],
    });

    const map = L.map(mapEl.value, {
        center: [defaultLat, defaultLng],
        zoom: 15,
        zoomControl: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    L.control.zoom({ position: 'topright' }).addTo(map);

    if (props.restaurantLat && props.restaurantLng) {
        const restaurantIcon = L.divIcon({
            className: 'custom-restaurant-pin',
            html: `
                    <div style="width: 38px; height: 38px; background: #1c1917; border: 3px solid #f97316; border-radius: 50%; box-shadow: 0 4px 10px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: #f97316;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/></svg>
                    </div>
                `,
            iconSize: [38, 38],
            iconAnchor: [19, 19],
        });

        L.marker([props.restaurantLat, props.restaurantLng], { icon: restaurantIcon })
            .addTo(map)
            .bindPopup(
                `<strong style="font-family: inherit; font-size: 12px; color: #ea580c;">🏪 موقع المطعم (${props.restaurantName})</strong>`,
            );
    }

    const marker = L.marker([defaultLat, defaultLng], {
        icon: customerPinIcon,
        draggable: true,
    }).addTo(map);

    marker
        .bindPopup(`
            <div style="font-family: inherit; text-align: right; direction: rtl; padding: 2px;">
                <p style="font-weight: 900; font-size: 13px; color: #ea580c; margin: 0 0 4px 0;">📍 نقطة استلام طلبك</p>
                <p style="font-size: 11px; color: #555; margin: 0;">اسحب الدبوس أو اضغط على الخريطة لتصحيح موقعك بدقة.</p>
            </div>
        `)
        .openPopup();

    marker.on('dragend', () => {
        const pos = marker.getLatLng();
        coords.value = { lat: pos.lat, lng: pos.lng };
        void reverseGeocode(pos.lat, pos.lng);
        marker.openPopup();
    });

    map.on('click', (e) => {
        marker.setLatLng(e.latlng);
        coords.value = { lat: e.latlng.lat, lng: e.latlng.lng };
        void reverseGeocode(e.latlng.lat, e.latlng.lng);
        marker.openPopup();
    });

    mapInstance.value = map;
    customerMarker.value = marker;

    if (props.autoLocateOnMount && !initialLocationAttempted.value && !props.initialLat) {
        initialLocationAttempted.value = true;
        handleGPS();
    }
});

onBeforeUnmount(() => {
    mapInstance.value?.remove();
    mapInstance.value = null;
});

const currentDistance = computed(() =>
    props.restaurantLat && props.restaurantLng
        ? calcDistance(props.restaurantLat, props.restaurantLng, coords.value.lat, coords.value.lng)
        : null,
);

const displayAddress = computed(() =>
    isReverseGeocoding.value
        ? 'جاري قراءة تفاصيل العنوان...'
        : addressText.value || `${coords.value.lat.toFixed(5)}, ${coords.value.lng.toFixed(5)}`,
);
</script>

<template>
    <div class="space-y-3">
        <div class="p-3 rounded-2xl bg-orange-500/10 border border-orange-500/20 text-xs text-orange-700 dark:text-orange-300 flex items-start gap-2">
            <Info class="w-4 h-4 shrink-0 mt-0.5 text-orange-600 dark:text-orange-400" />
            <div class="leading-relaxed">
                <span class="font-black">مكان التسليم محدد بدبوس:</span> يمكنك <strong>سحب الدبوس 📍</strong> أو <strong>الضغط مباشرة على الخريطة</strong> لتصحيح أو تغيير مكان استلام وجبتك بدقة.
            </div>
        </div>

        <div class="relative rounded-3xl overflow-hidden border-2 border-orange-400/40 dark:border-orange-500/30 shadow-md">
            <div ref="mapEl" class="w-full h-80 sm:h-96 z-0" style="min-height: 320px" />

            <button
                type="button"
                :disabled="isLocating"
                class="absolute bottom-4 right-4 z-[400] flex items-center gap-2 px-4 py-2.5 rounded-2xl bg-orange-600 hover:bg-orange-700 text-white font-black text-xs shadow-xl transition-all disabled:opacity-50 cursor-pointer active:scale-95"
                @click="handleGPS"
            >
                <Loader2 v-if="isLocating" class="w-4 h-4 animate-spin" />
                <Crosshair v-else class="w-4 h-4" />
                <span>{{ isLocating ? 'جاري التقاط GPS...' : '📍 التقاط موقعي الحالي GPS' }}</span>
            </button>

            <div
                v-if="currentDistance !== null"
                class="absolute top-4 left-4 z-[400] bg-stone-900/90 backdrop-blur-md text-white px-3.5 py-2 rounded-2xl text-xs font-black shadow-lg border border-white/10 flex items-center gap-2"
            >
                <Navigation class="w-3.5 h-3.5 text-orange-400" />
                <span>المسافة للمطعم: {{ currentDistance }} كم</span>
            </div>

            <div
                v-if="hasDetectedGps"
                class="absolute top-4 right-4 z-[400] bg-emerald-600/90 backdrop-blur-md text-white px-3 py-1.5 rounded-xl text-[11px] font-black shadow-md flex items-center gap-1.5"
            >
                <Check class="w-3.5 h-3.5" />
                <span>تم التقاط الموقع بنجاح</span>
            </div>
        </div>

        <div class="p-4 rounded-2xl bg-white dark:bg-stone-900 border border-stone-200 dark:border-stone-800 shadow-xs flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <div class="p-2.5 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-500 text-white shrink-0 shadow-md">
                    <MapPin class="w-5 h-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] font-bold text-orange-600 dark:text-orange-400">
                        عنوان الاستلام المحدد بالدبوس:
                    </p>
                    <p class="text-xs sm:text-sm font-black text-stone-900 dark:text-white truncate mt-0.5">
                        {{ displayAddress }}
                    </p>
                    <p class="text-[10px] font-mono text-stone-400 mt-0.5">
                        GPS: {{ coords.lat.toFixed(6) }}, {{ coords.lng.toFixed(6) }}
                    </p>
                </div>
            </div>

            <span class="text-xs font-bold text-orange-600 bg-orange-50 dark:bg-orange-950/40 px-3 py-1.5 rounded-xl shrink-0 hidden sm:inline">
                اسحب الدبوس للتعديل 📍
            </span>
        </div>

        <div class="space-y-2">
            <p class="text-[11px] font-bold text-stone-500 dark:text-stone-400">
                أو انقر للانتقال سريعاً إلى معالم وجامعات برج العرب:
            </p>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="(lm, idx) in BORG_EL_ARAB_LANDMARKS"
                    :key="idx"
                    type="button"
                    class="px-3 py-1.5 rounded-xl bg-stone-100 dark:bg-stone-800 hover:bg-orange-100 dark:hover:bg-orange-950/60 hover:text-orange-600 dark:hover:text-orange-300 text-xs font-bold text-stone-700 dark:text-stone-300 transition-all border border-stone-200 dark:border-stone-700 cursor-pointer"
                    @click="updateLocation(lm.lat, lm.lng, lm.name, true)"
                >
                    {{ lm.name }}
                </button>
            </div>
        </div>
    </div>
</template>
