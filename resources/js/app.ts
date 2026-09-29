import './bootstrap';
import '../css/app.css';

import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate';
import AdminLayout from './Layouts/AdminLayout.vue';
import RestaurantLayout from './Layouts/RestaurantLayout.vue';

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
            }

            return module;
        }),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        pinia.use(piniaPluginPersistedstate);

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .mount(el);
    },
    progress: {
        color: '#f97316',
        showSpinner: false,
        delay: 120,
    },
});
