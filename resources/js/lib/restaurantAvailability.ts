/** Helper for restaurant availability shown to customers. */

export type AvailabilityStatus = 'OPEN' | 'BUSY' | 'CLOSED';

export function resolveAvailability(
    restaurant: {
        status?: string;
        availability_status?: string | null;
        is_open?: boolean;
    } | null | undefined,
): AvailabilityStatus {
    if (!restaurant || restaurant.status !== 'ACTIVE') {
        return 'CLOSED';
    }

    if (restaurant.availability_status === 'OPEN' || restaurant.availability_status === 'BUSY' || restaurant.availability_status === 'CLOSED') {
        return restaurant.availability_status;
    }

    // Legacy fallback
    return restaurant.is_open ? 'OPEN' : 'CLOSED';
}

export function availabilityMeta(status: AvailabilityStatus): {
    label: string;
    short: string;
    badgeClass: string;
    canOrder: boolean;
} {
    switch (status) {
        case 'OPEN':
            return {
                label: 'مفتوح',
                short: 'مفتوح',
                badgeClass: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                canOrder: true,
            };
        case 'BUSY':
            return {
                label: 'مشغول',
                short: 'مشغول',
                badgeClass: 'bg-amber-50 text-amber-700 ring-amber-200',
                canOrder: true,
            };
        default:
            return {
                label: 'مغلق',
                short: 'مغلق',
                badgeClass: 'bg-stone-100 text-stone-600 ring-stone-200',
                canOrder: false,
            };
    }
}
