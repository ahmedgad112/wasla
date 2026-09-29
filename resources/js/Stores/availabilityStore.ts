import { defineStore } from 'pinia';
import axios from 'axios';
import type { AvailabilityStatus } from '../lib/restaurantAvailability';
import { resolveAvailability } from '../lib/restaurantAvailability';

type LiveAvailability = {
    status: string;
    availability_status: string;
};

type RestaurantLike = {
    id: number;
    status?: string;
    availability_status?: string | null;
    is_open?: boolean;
};

export const useAvailabilityStore = defineStore('availability', {
    state: () => ({
        byId: {} as Record<number, LiveAvailability>,
        timerId: null as ReturnType<typeof setInterval> | null,
        started: false,
        fetching: false,
    }),

    actions: {
        start(intervalMs = 5000): void {
            if (this.started) {
                return;
            }

            this.started = true;
            void this.fetch();
            this.timerId = setInterval(() => {
                if (typeof document !== 'undefined' && document.hidden) {
                    return;
                }

                void this.fetch();
            }, intervalMs);
        },

        stop(): void {
            if (this.timerId !== null) {
                clearInterval(this.timerId);
                this.timerId = null;
            }

            this.started = false;
        },

        async fetch(): Promise<void> {
            if (this.fetching) {
                return;
            }

            this.fetching = true;

            try {
                const { data } = await axios.get<{
                    restaurants: Array<{
                        id: number;
                        status: string;
                        availability_status: string;
                    }>;
                }>('/restaurants/availability-statuses', {
                    headers: { Accept: 'application/json' },
                });

                const next: Record<number, LiveAvailability> = {};

                for (const row of data.restaurants) {
                    next[row.id] = {
                        status: row.status,
                        availability_status: row.availability_status,
                    };
                }

                this.byId = next;
            } catch {
                // Keep the last known statuses if the poll fails.
            } finally {
                this.fetching = false;
            }
        },

        applyLive<T extends RestaurantLike>(restaurant: T): T {
            const live = this.byId[restaurant.id];

            if (!live) {
                return restaurant;
            }

            return {
                ...restaurant,
                status: live.status,
                availability_status: live.availability_status,
            };
        },

        resolve(restaurant: RestaurantLike | null | undefined): AvailabilityStatus {
            if (!restaurant) {
                return 'CLOSED';
            }

            return resolveAvailability(this.applyLive(restaurant));
        },
    },
});
