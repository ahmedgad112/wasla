import { computed, ref } from 'vue';
import { BORG_EL_ARAB_UNIVERSITIES, type UniversityLocation } from '../constants/universities';

export interface UserPlace {
    lat: number;
    lng: number;
    accuracy: number | null;
    universityId: string | null;
    label: string;
}

const STORAGE_KEY = 'wasla_user_place';
const CAMPUS_MATCH_KM = 5;
const CACHE_MS = 2 * 60 * 1000;

const place = ref<UserPlace | null>(readStoredPlace());
const status = ref<'idle' | 'locating' | 'ready' | 'denied' | 'unsupported'>(
    place.value ? 'ready' : 'idle',
);

let requestStarted = false;

function haversineKm(lat1: number, lon1: number, lat2: number, lon2: number): number {
    const earthRadiusKm = 6371;
    const dLat = ((lat2 - lat1) * Math.PI) / 180;
    const dLon = ((lon2 - lon1) * Math.PI) / 180;
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos((lat1 * Math.PI) / 180) *
            Math.cos((lat2 * Math.PI) / 180) *
            Math.sin(dLon / 2) *
            Math.sin(dLon / 2);

    return earthRadiusKm * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function nearestCampus(lat: number, lng: number): UniversityLocation | null {
    let closest: UniversityLocation | null = null;
    let closestKm = CAMPUS_MATCH_KM;

    for (const campus of BORG_EL_ARAB_UNIVERSITIES) {
        const distance = haversineKm(lat, lng, campus.latitude, campus.longitude);
        if (distance <= closestKm) {
            closest = campus;
            closestKm = distance;
        }
    }

    return closest;
}

function readStoredPlace(): UserPlace | null {
    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw) as UserPlace & { savedAt?: number };
        if (!parsed.savedAt || Date.now() - parsed.savedAt > CACHE_MS) {
            return null;
        }
        if (typeof parsed.lat !== 'number' || typeof parsed.lng !== 'number') {
            return null;
        }

        return {
            lat: parsed.lat,
            lng: parsed.lng,
            accuracy: parsed.accuracy ?? null,
            universityId: parsed.universityId ?? null,
            label: parsed.label || 'موقعك الحالي',
        };
    } catch {
        return null;
    }
}

function storePlace(next: UserPlace): void {
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ ...next, savedAt: Date.now() }));
}

function applyCoords(lat: number, lng: number, accuracy: number | null): void {
    const campus = nearestCampus(lat, lng);
    const next: UserPlace = {
        lat,
        lng,
        accuracy,
        universityId: campus?.id ?? null,
        label: campus?.shortName ?? 'موقعك الحالي',
    };

    place.value = next;
    status.value = 'ready';
    storePlace(next);
}

export function requestUserLocation(force = false): void {
    if (!force && (requestStarted || status.value === 'locating')) {
        return;
    }

    if (!navigator.geolocation) {
        status.value = 'unsupported';
        return;
    }

    requestStarted = true;
    status.value = place.value ? 'ready' : 'locating';

    navigator.geolocation.getCurrentPosition(
        (position) => {
            applyCoords(position.coords.latitude, position.coords.longitude, position.coords.accuracy);
        },
        (error) => {
            requestStarted = false;
            status.value = error.code === 1 ? 'denied' : place.value ? 'ready' : 'denied';
        },
        { enableHighAccuracy: true, timeout: 12000, maximumAge: 60_000 },
    );
}

export function distanceFromUserKm(lat?: number | null, lng?: number | null): number | null {
    if (!place.value || lat == null || lng == null || Number.isNaN(Number(lat)) || Number.isNaN(Number(lng))) {
        return null;
    }

    return Math.round(haversineKm(place.value.lat, place.value.lng, Number(lat), Number(lng)) * 10) / 10;
}

export function formatDistanceKm(distanceKm: number): string {
    if (distanceKm < 1) {
        return `${Math.max(100, Math.round(distanceKm * 1000))} م`;
    }

    return `${distanceKm.toFixed(1)} كم`;
}

export function sortByUserDistance<T extends { latitude?: number | null; longitude?: number | null; status?: string }>(
    items: T[],
): T[] {
    if (!place.value) {
        return items;
    }

    return [...items].sort((left, right) => {
        const leftActive = left.status === 'ACTIVE' ? 0 : 1;
        const rightActive = right.status === 'ACTIVE' ? 0 : 1;
        if (leftActive !== rightActive) {
            return leftActive - rightActive;
        }

        const leftDistance = distanceFromUserKm(left.latitude, left.longitude) ?? Number.POSITIVE_INFINITY;
        const rightDistance = distanceFromUserKm(right.latitude, right.longitude) ?? Number.POSITIVE_INFINITY;

        return leftDistance - rightDistance;
    });
}

export function useUserLocation() {
    const deliveryLabel = computed(() => {
        if (status.value === 'locating') {
            return 'جاري تحديد موقعك بالـ GPS...';
        }
        if (place.value) {
            return `التوصيل إلى ${place.value.label}`;
        }
        if (status.value === 'denied' || status.value === 'unsupported') {
            return 'فعّل GPS لتحديد موقعك';
        }

        return '';
    });

    return {
        place,
        status,
        deliveryLabel,
        requestUserLocation,
        distanceFromUserKm,
        formatDistanceKm,
        sortByUserDistance,
    };
}
