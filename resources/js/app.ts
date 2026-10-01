import './bootstrap';
import '../css/app.css';

import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate';
import AdminLayout from './Layouts/AdminLayout.vue';
import DeliveryLayout from './Layouts/DeliveryLayout.vue';
import GuestLayout from './Layouts/GuestLayout.vue';
import RestaurantLayout from './Layouts/RestaurantLayout.vue';
import { userCan } from './composables/usePermission';
import type { SharedProps } from './Types';

const appName = document.head.querySelector('meta[name="app-name"]')?.getAttribute('content') ?? 'وصلة';
const pages = import.meta.glob('./Pages/**/*.vue');

createInertiaApp({
    title: (title) => `${title} — ${appName}`,
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, pages).then((module) => {
            const page = module as { default: DefineComponent & { layout?: unknown } };

            if (name.startsWith('Admin/')) {
                page.default.layout ??= AdminLayout;
            } else if (name.startsWith('Restaurant/')) {
                page.default.layout ??= RestaurantLayout;
            } else if (name.startsWith('Delivery/') && name !== 'Delivery/Suspended') {
                page.default.layout ??= DeliveryLayout;
            } else if (name.startsWith('Public/') || name.startsWith('Customer/') || name.startsWith('Auth/')) {
                page.default.layout ??= GuestLayout;
            }

            return module;
        }),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        pinia.use(piniaPluginPersistedstate);

        const vueApp = createApp({ render: () => h(App, props) });
        vueApp.use(plugin);
        vueApp.config.globalProperties.$can = function (
            this: { $page?: { props?: SharedProps } },
            permission: string,
        ): boolean {
            const auth = this.$page?.props?.auth;

            return userCan(auth?.user?.role, auth?.permissions, permission);
        };
        vueApp.use(pinia).mount(el);

        router.on('finish', (event) => {
            const visit = event.detail.visit;

            if (!visit.completed || visit.method === 'get') {
                return;
            }

            router.flushAll();
            queueMicrotask(() => window.dispatchEvent(new Event('app:navigation-cache-flushed')));
        });
    },
    defaults: {
        prefetch: {
            cacheFor: ['45s', '10m'],
            hoverDelay: 1200,
        },
    },
    progress: {
        color: '#f97316',
        showSpinner: false,
        delay: 220,
    },
});
