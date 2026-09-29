<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';
import { Bike, Route, Clock, Navigation, Phone, RefreshCw } from '@lucide/vue';

const props = withDefaults(
    defineProps<{
        orderNumber: string;
        customerLat?: number | null;
        customerLng?: number | null;
        customerAddress: string;
        restaurantLat?: number | null;
        restaurantLng?: number | null;
        restaurantName?: string;
        initialDriverLat?: number | null;
        initialDriverLng?: number | null;
        driverName?: string;
        driverPhone?: string;
        orderStatus: string;
    }>(),
    {
        restaurantLat: 30.8725,
        restaurantLng: 29.584,
        restaurantName: 'المطعم',
        driverName: 'كابتن التوصيل',
    },
);

const mapEl = ref<HTMLElement | null>(null);
const mapInstance = ref<L.Map | null>(null);
const driverMarker = ref<L.Marker | null>(null);
const routeLine = ref<L.Polyline | null>(null);
let pollInterval: ReturnType<typeof setInterval> | null = null;
let isMounted = true;

const hasCustomerPin = computed(
    () =>
        props.customerLat !== null &&
        props.customerLat !== undefined &&
        props.customerLng !== null &&
        props.customerLng !== undefined &&
        Number.isFinite(Number(props.customerLat)) &&
        Number.isFinite(Number(props.customerLng)),
);

const hasRestaurantPin = computed(
    () =>
        props.restaurantLat !== null &&
        props.restaurantLat !== undefined &&
        props.restaurantLng !== null &&
        props.restaurantLng !== undefined &&
        Number.isFinite(Number(props.restaurantLat)) &&
        Number.isFinite(Number(props.restaurantLng)),
);

const cLat = computed(() => (hasCustomerPin.value ? Number(props.customerLat) : 0));
const cLng = computed(() => (hasCustomerPin.value ? Number(props.customerLng) : 0));
const rLat = computed(() => (hasRestaurantPin.value ? Number(props.restaurantLat) : 0));
const rLng = computed(() => (hasRestaurantPin.value ? Number(props.restaurantLng) : 0));

const driverCoords = ref<{ lat: number; lng: number } | null>(
    props.initialDriverLat && props.initialDriverLng
        ? { lat: Number(props.initialDriverLat), lng: Number(props.initialDriverLng) }
        : null,
);
const distanceKm = ref<number | null>(null);

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

const estimatedMinutes = computed(() =>
    distanceKm.value !== null ? Math.max(2, Math.round(distanceKm.value * 3.2)) : null,
);

const statusMessage = computed(() =>
    props.orderStatus === 'OUT_FOR_DELIVERY'
        ? `الكابتن (${props.driverName}) يقود باتجاه موقعك الآن 🛵`
        : `الكابتن (${props.driverName}) في طريقه للمطعم لاستلام وجبتك`,
);

const initMap = (): void => {
    if (!hasCustomerPin.value || !hasRestaurantPin.value) {
        return;
    }
    if (!mapEl.value || mapInstance.value) {
        return;
    }

    const map = L.map(mapEl.value, {
        center: [cLat.value, cLng.value],
        zoom: 14,
        zoomControl: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    L.control.zoom({ position: 'topright' }).addTo(map);

    const restIcon = L.divIcon({
        className: 'cust-track-restaurant',
        html: `
                <div style="position: relative; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                    <div style="width: 36px; height: 36px; background: #ea580c; border: 3px solid #ffffff; border-radius: 50%; box-shadow: 0 4px 12px rgba(234,88,12,0.4); display: flex; align-items: center; justify-content: center; color: white;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/></svg>
                    </div>
                </div>
            `,
        iconSize: [40, 40],
        iconAnchor: [20, 20],
    });

    L.marker([rLat.value, rLng.value], { icon: restIcon })
        .addTo(map)
        .bindPopup(
            `<div style="direction: rtl; text-align: right; font-family: inherit;"><strong>🏪 ${props.restaurantName}</strong><p style="margin:0;font-size:11px;color:#666;">نقطة تحضير الوجبة</p></div>`,
        );

    const custIcon = L.divIcon({
        className: 'cust-track-destination',
        html: `
                <div style="position: relative; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                    <div style="position: absolute; inset: 0; background: #10b981; opacity: 0.35; border-radius: 50%; animation: ping 1.8s cubic-bezier(0, 0, 0.2, 1) infinite;"></div>
                    <div style="position: relative; width: 38px; height: 38px; background: #10b981; border: 3px solid #ffffff; border-radius: 50%; box-shadow: 0 4px 14px rgba(16,185,129,0.5); display: flex; align-items: center; justify-content: center; color: white;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                </div>
            `,
        iconSize: [44, 44],
        iconAnchor: [22, 22],
    });

    L.marker([cLat.value, cLng.value], { icon: custIcon })
        .addTo(map)
        .bindPopup(
            `<div style="direction: rtl; text-align: right; font-family: inherit;"><strong style="color:#10b981;">📍 مكان استلامك للطلب</strong><p style="margin:2px 0 0 0;font-size:11px;color:#333;">${props.customerAddress}</p></div>`,
        )
        .openPopup();

    const bounds = L.latLngBounds([
        [rLat.value, rLng.value],
        [cLat.value, cLng.value],
    ]);
    map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });

    mapInstance.value = map;
};

const fetchDriverLocation = async (): Promise<void> => {
    try {
        const res = await axios.get(`/orders/${props.orderNumber}/driver-location`);
        if (!isMounted) {
            return;
        }

        const data = res.data;
        if (data.driver && data.driver.latitude && data.driver.longitude) {
            const dLat = Number(data.driver.latitude);
            const dLng = Number(data.driver.longitude);
            driverCoords.value = { lat: dLat, lng: dLng };

            const dist = calcDistance(dLat, dLng, cLat.value, cLng.value);
            distanceKm.value = dist;

            const map = mapInstance.value;
            if (!map) {
                return;
            }

            if (!driverMarker.value) {
                const driverIcon = L.divIcon({
                    className: 'cust-live-driver-pin',
                    html: `
                                <div style="position: relative; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                    <div style="position: absolute; inset: 0; background: #0284c7; opacity: 0.35; border-radius: 50%; animation: ping 1.2s cubic-bezier(0, 0, 0.2, 1) infinite;"></div>
                                    <div style="position: relative; width: 42px; height: 42px; background: #0f172a; border: 3px solid #38bdf8; border-radius: 50%; box-shadow: 0 6px 16px rgba(2,132,199,0.5); display: flex; align-items: center; justify-content: center; color: #38bdf8;">
                                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="18.5" cy="17.5" r="3.5"/><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="15" cy="5" r="1"/><path d="M12 17.5V14l-3-3 4-3 2 3h2"/></svg>
                                    </div>
                                </div>
                            `,
                    iconSize: [50, 50],
                    iconAnchor: [25, 25],
                });

                driverMarker.value = L.marker([dLat, dLng], { icon: driverIcon, zIndexOffset: 1000 })
                    .addTo(map)
                    .bindPopup(
                        `<div style="direction: rtl; text-align: right; font-family: inherit;"><strong style="color:#0284c7;">🛵 ${props.driverName}</strong><p style="margin:2px 0 0 0;font-size:11px;color:#555;">في الطريق إليك الآن</p></div>`,
                    );
            } else {
                driverMarker.value.setLatLng([dLat, dLng]);
            }

            const route = await axios.get(
                `https://router.project-osrm.org/route/v1/driving/${dLng},${dLat};${cLng.value},${cLat.value}`,
                { params: { overview: 'full', geometries: 'geojson' } },
            );
            const coordinates = route.data?.routes?.[0]?.geometry?.coordinates?.map(
                ([lng, lat]: [number, number]) => [lat, lng],
            );
            if (coordinates?.length) {
                if (!routeLine.value) {
                    routeLine.value = L.polyline(coordinates, {
                        color: '#0284c7',
                        weight: 5,
                        opacity: 0.85,
                        lineCap: 'round',
                        lineJoin: 'round',
                    }).addTo(map);
                } else {
                    routeLine.value.setLatLngs(coordinates);
                }
            }
        }
    } catch {
        // Silently handle poll error
    }
};

const startPolling = (): void => {
    if (!hasCustomerPin.value || !hasRestaurantPin.value) {
        return;
    }
    void fetchDriverLocation();
    pollInterval = setInterval(() => {
        void fetchDriverLocation();
    }, 4000);
};

const handleFocusDriver = (): void => {
    const map = mapInstance.value;
    if (map && driverCoords.value) {
        map.setView([driverCoords.value.lat, driverCoords.value.lng], 16, { animate: true });
    }
};

const handleFitAll = (): void => {
    const map = mapInstance.value;
    if (!map) {
        return;
    }
    const points: [number, number][] = [
        [rLat.value, rLng.value],
        [cLat.value, cLng.value],
    ];
    if (driverCoords.value) {
        points.push([driverCoords.value.lat, driverCoords.value.lng]);
    }
    map.fitBounds(L.latLngBounds(points), { padding: [40, 40], maxZoom: 16 });
};

onMounted(() => {
    isMounted = true;
    initMap();
    startPolling();
});

onBeforeUnmount(() => {
    isMounted = false;
    if (pollInterval) {
        clearInterval(pollInterval);
    }
    mapInstance.value?.remove();
    mapInstance.value = null;
});

watch([hasCustomerPin, hasRestaurantPin], () => {
    if (hasCustomerPin.value && hasRestaurantPin.value && !mapInstance.value) {
        initMap();
        startPolling();
    }
});
</script>

<template>
    <div
        v-if="!hasCustomerPin || !hasRestaurantPin"
        class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm font-bold text-amber-800"
    >
        لا يمكن عرض التتبع بدقة لأن موقع العميل أو المطعم غير محدد. افتح الطلب وحدد الدبوس الصحيح.
    </div>
    <div v-else class="space-y-3 animate-fade-in">
        <div class="p-4 sm:p-5 rounded-3xl bg-gradient-to-r from-slate-900 via-stone-900 to-sky-950 text-white shadow-xl border border-sky-500/20 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-sky-500/20 text-sky-400 border border-sky-400/30 flex items-center justify-center shrink-0">
                        <Bike class="w-6 h-6 animate-bounce" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-bold text-sky-300">
                                تتبع مسار التوصيل المباشر 🛰️
                            </span>
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
                        </div>
                        <h3 class="text-sm sm:text-base font-black text-white mt-0.5">
                            {{ statusMessage }}
                        </h3>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs">
                    <template v-if="distanceKm !== null">
                        <div class="px-3 py-1.5 rounded-xl bg-white/10 backdrop-blur-md flex items-center gap-1.5">
                            <Route class="w-3.5 h-3.5 text-orange-400" />
                            <span class="font-bold">على بعد {{ distanceKm }} كم</span>
                        </div>
                        <div class="px-3 py-1.5 rounded-xl bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 flex items-center gap-1.5">
                            <Clock class="w-3.5 h-3.5" />
                            <span class="font-bold">يصل خلال ~{{ estimatedMinutes }} دقيقة</span>
                        </div>
                    </template>
                    <div
                        v-else
                        class="px-3 py-1.5 rounded-xl bg-white/10 backdrop-blur-md flex items-center gap-1.5 text-[11px] text-stone-300"
                    >
                        <RefreshCw class="w-3 h-3 animate-spin text-sky-400" />
                        <span>جاري استلام إشارة كابتن التوصيل...</span>
                    </div>
                </div>
            </div>

            <div
                v-if="driverPhone"
                class="pt-2 border-t border-white/10 flex items-center justify-between text-xs"
            >
                <span class="text-stone-400">للتواصل مع الكابتن:</span>
                <a
                    :href="`tel:${driverPhone}`"
                    class="px-3 py-1 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold flex items-center gap-1.5 transition text-xs"
                >
                    <Phone class="w-3.5 h-3.5" />
                    <span>اتصال بالكابتن ({{ driverPhone }})</span>
                </a>
            </div>
        </div>

        <div class="relative rounded-3xl overflow-hidden border-2 border-sky-500/20 shadow-lg">
            <div ref="mapEl" class="w-full h-80 sm:h-96 z-0" style="min-height: 320px" />

            <div class="absolute top-4 left-4 z-[400] flex flex-col gap-2">
                <button
                    v-if="driverCoords"
                    type="button"
                    class="p-2.5 rounded-2xl bg-sky-600 hover:bg-sky-700 text-white shadow-lg backdrop-blur-md font-bold text-xs flex items-center gap-1.5 transition cursor-pointer"
                    title="مكان الكابتن"
                    @click="handleFocusDriver"
                >
                    <Bike class="w-4 h-4" />
                    <span class="hidden sm:inline">مكان الكابتن</span>
                </button>

                <button
                    type="button"
                    class="p-2.5 rounded-2xl bg-stone-900/80 hover:bg-stone-900 text-white shadow-lg backdrop-blur-md font-bold text-xs flex items-center gap-1.5 transition cursor-pointer"
                    title="كامل المسار"
                    @click="handleFitAll"
                >
                    <Navigation class="w-4 h-4" />
                    <span class="hidden sm:inline">كامل المسار</span>
                </button>
            </div>

            <div class="absolute bottom-4 right-4 z-[400] bg-stone-900/90 backdrop-blur-md text-white px-3 py-1.5 rounded-xl text-[10px] font-bold shadow-md border border-white/10 flex items-center gap-3">
                <span class="flex items-center gap-1 text-sky-400">
                    <span class="w-2 h-2 rounded-full bg-sky-400 animate-pulse" /> الكابتن
                </span>
                <span class="flex items-center gap-1 text-emerald-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-400" /> موقعك
                </span>
                <span class="flex items-center gap-1 text-orange-400">
                    <span class="w-2 h-2 rounded-full bg-orange-400" /> المطعم
                </span>
            </div>
        </div>
    </div>
</template>
