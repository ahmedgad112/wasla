<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import axios from 'axios';
import { MapPin, Route, Store, Maximize2, Navigation, Clock, Gauge, ArrowUp } from '@lucide/vue';

const props = withDefaults(
    defineProps<{
        customerLat?: number | null;
        customerLng?: number | null;
        customerAddress: string;
        customerName?: string;
        customerPhone?: string;
        restaurantLat?: number | null;
        restaurantLng?: number | null;
        restaurantName?: string;
        restaurantAddress?: string;
        restaurantPhone?: string;
        orderStatus?: string;
        orderId?: number;
    }>(),
    {
        customerName: 'العميل',
        restaurantLat: 30.87,
        restaurantLng: 29.58,
        restaurantName: 'المطعم',
        orderStatus: 'ASSIGNED_TO_DRIVER',
    },
);

const LANDMARK_COORDS: Record<string, [number, number]> = {
    BATU: [30.8756, 29.5842],
    تكنولوجية: [30.8756, 29.5842],
    EJUST: [30.8648, 29.5741],
    اليابانية: [30.8648, 29.5741],
    سنجور: [30.8805, 29.5912],
    سموحة: [30.858, 29.593],
    الهوارية: [30.892, 29.601],
    'الحي الأول': [30.871, 29.589],
    'الحي الثاني': [30.865, 29.595],
    مجاورة: [30.872, 29.588],
};

function resolveCustomerCoords(
    lat?: number | null,
    lng?: number | null,
    address?: string,
): [number, number] {
    if (lat && lng && !isNaN(Number(lat)) && !isNaN(Number(lng))) {
        return [Number(lat), Number(lng)];
    }
    if (address) {
        for (const [key, coords] of Object.entries(LANDMARK_COORDS)) {
            if (address.includes(key)) {
                return coords;
            }
        }
    }
    return [30.8752, 29.5841];
}

const mapEl = ref<HTMLElement | null>(null);
const mapInstance = ref<L.Map | null>(null);
const driverMarker = ref<L.Marker | null>(null);
const roadPolyline = ref<L.Polyline | null>(null);
const lastSyncTime = ref(0);
const prevPosition = ref<{ lat: number; lng: number; time: number } | null>(null);
let watchId: number | null = null;

const rLat = Number(props.restaurantLat) || 30.8725;
const rLng = Number(props.restaurantLng) || 29.584;
const [cLat, cLng] = resolveCustomerCoords(props.customerLat, props.customerLng, props.customerAddress);

const driverCoords = ref<{ lat: number; lng: number } | null>(null);
const isGpsActive = ref(false);
const gpsError = ref<string | null>(null);
const isNavMode = ref(true);
const currentSpeedKmh = ref(0);
const currentHeading = ref<number | null>(null);
const roadDistanceMeters = ref<number | null>(null);
const roadDurationSecs = ref<number | null>(null);
const nextInstruction = ref('ابدأ بالتحرك نحو وجهتك المحددة');

const isOutForDelivery = computed(() => props.orderStatus === 'OUT_FOR_DELIVERY');
const targetDestinationCoords = computed((): [number, number] =>
    isOutForDelivery.value ? [cLat, cLng] : [rLat, rLng],
);
const targetDestinationName = computed(() =>
    isOutForDelivery.value ? props.customerName : props.restaurantName,
);

const calcDistanceKm = (lat1: number, lon1: number, lat2: number, lon2: number): number => {
    const R = 6371;
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLon = ((lon2 - lon1) * Math.PI) / 180;
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos((lat1 * Math.PI) / 180) * Math.cos((lat2 * Math.PI) / 180) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return Math.round(R * c * 10) / 10;
};

const calculateBearing = (startLat: number, startLng: number, destLat: number, destLng: number): number => {
    const startLatRad = (startLat * Math.PI) / 180;
    const startLngRad = (startLng * Math.PI) / 180;
    const destLatRad = (destLat * Math.PI) / 180;
    const destLngRad = (destLng * Math.PI) / 180;

    const y = Math.sin(destLngRad - startLngRad) * Math.cos(destLatRad);
    const x =
        Math.cos(startLatRad) * Math.sin(destLatRad) -
        Math.sin(startLatRad) * Math.cos(destLatRad) * Math.cos(destLngRad - startLngRad);
    const brng = (Math.atan2(y, x) * 180) / Math.PI;
    return (brng + 360) % 360;
};

const fetchRoadRoute = async (fromLat: number, fromLng: number, toLat: number, toLng: number): Promise<void> => {
    try {
        const url = `https://router.project-osrm.org/route/v1/driving/${fromLng},${fromLat};${toLng},${toLat}?overview=full&geometries=geojson&steps=true`;
        const res = await fetch(url);
        if (!res.ok) {
            return;
        }
        const data = await res.json();

        if (data.routes && data.routes.length > 0) {
            const route = data.routes[0];
            roadDistanceMeters.value = Math.round(route.distance);
            roadDurationSecs.value = Math.round(route.duration);

            if (route.legs && route.legs[0]?.steps && route.legs[0].steps.length > 0) {
                const nextStep =
                    route.legs[0].steps.find((s: { distance: number }) => s.distance > 20) ||
                    route.legs[0].steps[0];
                if (nextStep) {
                    const maneuver = nextStep.maneuver;
                    const modifier = maneuver.modifier ? ` (${maneuver.modifier})` : '';
                    const name = nextStep.name ? ` على ${nextStep.name}` : '';
                    nextInstruction.value = `تابع السير${modifier}${name} لمسافة ${Math.round(nextStep.distance)}م نحو ${targetDestinationName.value}`;
                }
            }

            const coords = route.geometry.coordinates.map((pt: [number, number]) => [pt[1], pt[0]]);
            const map = mapInstance.value;
            if (map) {
                if (!roadPolyline.value) {
                    roadPolyline.value = L.polyline(coords, {
                        color: '#10b981',
                        weight: 6,
                        opacity: 0.9,
                        lineCap: 'round',
                        lineJoin: 'round',
                    }).addTo(map);
                } else {
                    roadPolyline.value.setLatLngs(coords);
                }
            }
        }
    } catch {
        const map = mapInstance.value;
        if (map) {
            const fallbackCoords: [number, number][] = [
                [fromLat, fromLng],
                [toLat, toLng],
            ];
            if (!roadPolyline.value) {
                roadPolyline.value = L.polyline(fallbackCoords, {
                    color: '#10b981',
                    weight: 5,
                    opacity: 0.8,
                    dashArray: '8, 8',
                }).addTo(map);
            } else {
                roadPolyline.value.setLatLngs(fallbackCoords);
            }
        }
    }
};

const displayDistanceKm = computed(() =>
    roadDistanceMeters.value
        ? (roadDistanceMeters.value / 1000).toFixed(1)
        : driverCoords.value
          ? calcDistanceKm(
                driverCoords.value.lat,
                driverCoords.value.lng,
                targetDestinationCoords.value[0],
                targetDestinationCoords.value[1],
            )
          : calcDistanceKm(rLat, rLng, cLat, cLng),
);

const displayMinutes = computed(() =>
    roadDurationSecs.value
        ? Math.max(1, Math.round(roadDurationSecs.value / 60))
        : Math.max(2, Math.round(Number(displayDistanceKm.value) * 3.2)),
);

const handleFitAll = (): void => {
    isNavMode.value = false;
    const map = mapInstance.value;
    if (!map) {
        return;
    }
    const pts: [number, number][] = [
        [rLat, rLng],
        [cLat, cLng],
    ];
    if (driverCoords.value) {
        pts.push([driverCoords.value.lat, driverCoords.value.lng]);
    }
    map.fitBounds(pts, { padding: [40, 40], maxZoom: 16 });
};

const handleToggleNavMode = (): void => {
    const next = !isNavMode.value;
    isNavMode.value = next;
    if (next && driverCoords.value && mapInstance.value) {
        mapInstance.value.setView([driverCoords.value.lat, driverCoords.value.lng], 17, { animate: true });
    }
};

const focusRestaurant = (): void => {
    isNavMode.value = false;
    mapInstance.value?.setView([rLat, rLng], 17, { animate: true });
};

const focusCustomer = (): void => {
    isNavMode.value = false;
    mapInstance.value?.setView([cLat, cLng], 17, { animate: true });
};

const handleGpsSuccess = (pos: GeolocationPosition): void => {
    const { latitude, longitude, speed, heading } = pos.coords;
    const now = Date.now();
    driverCoords.value = { lat: latitude, lng: longitude };
    isGpsActive.value = true;
    gpsError.value = null;

    if (speed !== null && !isNaN(speed) && speed >= 0) {
        currentSpeedKmh.value = Math.round(speed * 3.6);
    } else if (prevPosition.value) {
        const dtSeconds = (now - prevPosition.value.time) / 1000;
        if (dtSeconds > 1 && dtSeconds < 30) {
            const distKm = calcDistanceKm(prevPosition.value.lat, prevPosition.value.lng, latitude, longitude);
            const calcKmh = Math.round(distKm / (dtSeconds / 3600));
            currentSpeedKmh.value = Math.min(120, Math.max(0, calcKmh));
        }
    }

    if (heading !== null && !isNaN(heading)) {
        currentHeading.value = Math.round(heading);
    } else if (prevPosition.value) {
        const bearing = calculateBearing(prevPosition.value.lat, prevPosition.value.lng, latitude, longitude);
        currentHeading.value = Math.round(bearing);
    }

    prevPosition.value = { lat: latitude, lng: longitude, time: now };

    const map = mapInstance.value;
    if (map) {
        const rot = currentHeading.value || 0;
        const driverIcon = L.divIcon({
            className: 'uber-driver-active-pin',
            html: `
                        <div style="position: relative; width: 52px; height: 52px; display: flex; align-items: center; justify-content: center;">
                            <div style="position: absolute; inset: 0; background: #0284c7; opacity: 0.35; border-radius: 50%; animation: ping 1.2s cubic-bezier(0, 0, 0.2, 1) infinite;"></div>
                            <div style="position: relative; width: 44px; height: 44px; background: #0f172a; border: 3.5px solid #38bdf8; border-radius: 50%; box-shadow: 0 6px 20px rgba(2,132,199,0.7); display: flex; align-items: center; justify-content: center; color: #38bdf8; transform: rotate(${rot}deg); transition: transform 0.4s ease;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m12 2 7 19-7-4-7 4 7-19z"/></svg>
                            </div>
                        </div>
                    `,
            iconSize: [52, 52],
            iconAnchor: [26, 26],
        });

        if (!driverMarker.value) {
            driverMarker.value = L.marker([latitude, longitude], { icon: driverIcon, zIndexOffset: 1000 })
                .addTo(map)
                .bindPopup(`<strong style="direction:rtl;text-align:right;color:#0284c7;">🛵 موقعك المباشر في الطريق</strong>`);
        } else {
            driverMarker.value.setLatLng([latitude, longitude]);
            driverMarker.value.setIcon(driverIcon);
        }

        if (isNavMode.value) {
            map.setView([latitude, longitude], 17, { animate: true, duration: 0.5 });
        }

        void fetchRoadRoute(
            latitude,
            longitude,
            targetDestinationCoords.value[0],
            targetDestinationCoords.value[1],
        );
    }

    if (props.orderId && now - lastSyncTime.value > 5000) {
        lastSyncTime.value = now;
        axios
            .post(`/delivery/orders/${props.orderId}/location`, {
                latitude,
                longitude,
                speed: currentSpeedKmh.value,
                heading: currentHeading.value,
            })
            .catch(() => {});
    }
};

const handleGpsError = (err: GeolocationPositionError): void => {
    isGpsActive.value = false;
    gpsError.value = err.code === 1 ? 'يرجى تفعيل صلاحية الموقع.' : 'جاري البحث عن إشارة GPS...';
};

const startGpsWatch = (): void => {
    if (!navigator.geolocation) {
        gpsError.value = 'خاصية GPS غير مدعومة.';
        return;
    }

    watchId = navigator.geolocation.watchPosition(handleGpsSuccess, handleGpsError, {
        enableHighAccuracy: true,
        timeout: 12000,
        maximumAge: 2000,
    });
};

onMounted(() => {
    if (!mapEl.value || mapInstance.value) {
        return;
    }

    const map = L.map(mapEl.value, {
        center: [rLat, rLng],
        zoom: 15,
        zoomControl: false,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    L.control.zoom({ position: 'topright' }).addTo(map);

    const restIcon = L.divIcon({
        className: 'uber-restaurant-marker',
        html: `
                <div style="position: relative; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                    <div style="width: 40px; height: 40px; background: #ea580c; border: 3px solid #ffffff; border-radius: 50%; box-shadow: 0 4px 14px rgba(234,88,12,0.5); display: flex; align-items: center; justify-content: center; color: #ffffff;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/></svg>
                    </div>
                </div>
            `,
        iconSize: [44, 44],
        iconAnchor: [22, 22],
    });

    L.marker([rLat, rLng], { icon: restIcon })
        .addTo(map)
        .bindPopup(`
                <div style="font-family: inherit; text-align: right; direction: rtl; padding: 4px;">
                    <span style="font-size: 10px; font-weight: 800; color: #ea580c;">نقطة الاستلام</span>
                    <h4 style="font-weight: 900; font-size: 13px; margin: 2px 0;">${props.restaurantName}</h4>
                    <p style="font-size: 11px; color: #666; margin: 0;">${props.restaurantAddress || 'برج العرب'}</p>
                </div>
            `);

    const custIcon = L.divIcon({
        className: 'uber-customer-marker',
        html: `
                <div style="position: relative; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                    <div style="position: absolute; inset: 0; background: #10b981; opacity: 0.35; border-radius: 50%; animation: ping 1.8s cubic-bezier(0, 0, 0.2, 1) infinite;"></div>
                    <div style="width: 40px; height: 40px; background: #10b981; border: 3px solid #ffffff; border-radius: 50%; box-shadow: 0 4px 14px rgba(16,185,129,0.5); display: flex; align-items: center; justify-content: center; color: #ffffff;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                </div>
            `,
        iconSize: [48, 48],
        iconAnchor: [24, 24],
    });

    L.marker([cLat, cLng], { icon: custIcon })
        .addTo(map)
        .bindPopup(`
                <div style="font-family: inherit; text-align: right; direction: rtl; padding: 4px;">
                    <span style="font-size: 10px; font-weight: 800; color: #10b981;">نقطة التسليم للعميل</span>
                    <h4 style="font-weight: 900; font-size: 13px; margin: 2px 0;">${props.customerName}</h4>
                    <p style="font-size: 11px; color: #444; margin: 0;">${props.customerAddress}</p>
                </div>
            `);

    map.fitBounds(
        [
            [rLat, rLng],
            [cLat, cLng],
        ],
        { padding: [50, 50], maxZoom: 15 },
    );

    mapInstance.value = map;
    startGpsWatch();
});

onBeforeUnmount(() => {
    if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
    }
    mapInstance.value?.remove();
    mapInstance.value = null;
});

watch(
    () => props.orderStatus,
    () => {
        if (driverCoords.value) {
            void fetchRoadRoute(
                driverCoords.value.lat,
                driverCoords.value.lng,
                targetDestinationCoords.value[0],
                targetDestinationCoords.value[1],
            );
        }
    },
);
</script>

<template>
    <div class="space-y-3">
        <div class="p-4 sm:p-5 rounded-3xl bg-slate-950 text-white shadow-2xl border border-sky-500/30 space-y-4">
            <div class="flex items-center gap-3.5 bg-sky-950/60 p-3.5 rounded-2xl border border-sky-500/30">
                <div class="w-12 h-12 rounded-2xl bg-sky-500 text-white flex items-center justify-center font-black text-xl shadow-lg shadow-sky-500/30 shrink-0">
                    <ArrowUp class="w-7 h-7" />
                </div>
                <div class="flex-1 min-w-0">
                    <span class="text-[10px] font-bold text-sky-400 uppercase tracking-wide block">
                        {{ isOutForDelivery ? 'التوجه إلى العميل 🎯' : 'التوجه إلى المطعم للاستلام 🏪' }}
                    </span>
                    <h3 class="text-sm sm:text-base font-black text-white truncate mt-0.5">
                        {{ nextInstruction }}
                    </h3>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="p-3 rounded-2xl bg-white/5 border border-white/10 flex flex-col items-center justify-center">
                    <div class="flex items-center gap-1 text-[11px] font-bold text-sky-400 mb-0.5">
                        <Gauge class="w-3.5 h-3.5" />
                        <span>السرعة</span>
                    </div>
                    <span class="text-xl sm:text-2xl font-black text-white font-mono">
                        {{ currentSpeedKmh }}
                    </span>
                    <span class="text-[10px] text-stone-400">كم/س</span>
                </div>

                <div class="p-3 rounded-2xl bg-white/5 border border-white/10 flex flex-col items-center justify-center">
                    <div class="flex items-center gap-1 text-[11px] font-bold text-orange-400 mb-0.5">
                        <Route class="w-3.5 h-3.5" />
                        <span>المسافة</span>
                    </div>
                    <span class="text-xl sm:text-2xl font-black text-white font-mono">
                        {{ displayDistanceKm }}
                    </span>
                    <span class="text-[10px] text-stone-400">كم</span>
                </div>

                <div class="p-3 rounded-2xl bg-white/5 border border-white/10 flex flex-col items-center justify-center">
                    <div class="flex items-center gap-1 text-[11px] font-bold text-emerald-400 mb-0.5">
                        <Clock class="w-3.5 h-3.5" />
                        <span>الوصول</span>
                    </div>
                    <span class="text-xl sm:text-2xl font-black text-emerald-400 font-mono">
                        ~{{ displayMinutes }}
                    </span>
                    <span class="text-[10px] text-stone-400">دقيقة</span>
                </div>
            </div>

            <div class="flex items-center justify-between text-[11px] pt-1 text-stone-300">
                <div class="flex items-center gap-1.5">
                    <span
                        :class="[
                            'w-2.5 h-2.5 rounded-full',
                            isGpsActive ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400',
                        ]"
                    />
                    <span>{{ isGpsActive ? 'إشارة GPS دقيقة ومباشرة 🟢' : gpsError || 'جاري التقاط إشارة GPS...' }}</span>
                </div>
                <span class="text-sky-300 font-bold">
                    {{ isNavMode ? '🧭 وضع الملاحة المقرب مفعّل' : '🗺️ وضع العرض الشامل' }}
                </span>
            </div>
        </div>

        <div class="relative rounded-3xl overflow-hidden border-2 border-sky-500/30 shadow-xl">
            <div ref="mapEl" class="w-full h-80 sm:h-96 z-0" style="min-height: 340px" />

            <div class="absolute top-4 left-4 z-[400] flex flex-col gap-2">
                <button
                    type="button"
                    :class="[
                        'p-2.5 rounded-2xl font-bold text-xs flex items-center gap-1.5 shadow-xl transition-all cursor-pointer',
                        isNavMode
                            ? 'bg-sky-600 text-white ring-2 ring-sky-400/60 shadow-sky-600/50'
                            : 'bg-stone-900/90 text-white hover:bg-stone-900',
                    ]"
                    title="وضع الملاحة والقيادة المقربة"
                    @click="handleToggleNavMode"
                >
                    <Navigation class="w-4 h-4" />
                    <span class="hidden sm:inline">وضع القيادة</span>
                </button>

                <button
                    type="button"
                    class="p-2.5 rounded-2xl bg-stone-900/90 hover:bg-stone-900 text-white shadow-xl font-bold text-xs flex items-center gap-1.5 transition cursor-pointer"
                    title="عرض المسار بالكامل"
                    @click="handleFitAll"
                >
                    <Maximize2 class="w-4 h-4" />
                    <span class="hidden sm:inline">كامل المسار</span>
                </button>
            </div>

            <div class="absolute bottom-4 left-4 z-[400] flex items-center gap-2">
                <button
                    type="button"
                    class="px-3 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-black shadow-md flex items-center gap-1.5 transition cursor-pointer"
                    @click="focusRestaurant"
                >
                    <Store class="w-3.5 h-3.5" />
                    <span>المطعم</span>
                </button>

                <button
                    type="button"
                    class="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-md flex items-center gap-1.5 transition cursor-pointer"
                    @click="focusCustomer"
                >
                    <MapPin class="w-3.5 h-3.5" />
                    <span>العميل</span>
                </button>
            </div>

            <div class="absolute bottom-4 right-4 z-[400] bg-stone-950/90 backdrop-blur-md text-white px-3 py-1.5 rounded-xl text-[10px] font-bold shadow-md border border-white/10 flex items-center gap-3">
                <span class="flex items-center gap-1 text-sky-400">
                    <span class="w-2 h-2 rounded-full bg-sky-400 animate-pulse" /> أنت
                </span>
                <span class="flex items-center gap-1 text-orange-400">
                    <span class="w-2 h-2 rounded-full bg-orange-400" /> المطعم
                </span>
                <span class="flex items-center gap-1 text-emerald-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-400" /> العميل
                </span>
            </div>
        </div>
    </div>
</template>
