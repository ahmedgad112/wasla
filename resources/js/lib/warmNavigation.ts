import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';

const pageModules = import.meta.glob('../Pages/**/*.vue');

const hrefComponents: Record<string, string> = {
    '/': 'Public/Home',
    '/offers': 'Public/Offers',
    '/restaurants': 'Public/Restaurants',
    '/leaderboard': 'Public/Leaderboard',
    '/contact': 'Public/Contact',
    '/login': 'Auth/Login',
    '/register': 'Auth/CustomerRegister',
    '/cart': 'Customer/Cart',
    '/checkout': 'Customer/Checkout',
    '/orders': 'Customer/Orders',
    '/profile': 'Customer/Profile',
    '/customer/orders': 'Customer/Orders',
    '/customer/profile': 'Customer/Profile',
    '/customer/dashboard': 'Customer/Dashboard',
    '/customer/cart': 'Customer/Cart',
    '/customer/checkout': 'Customer/Checkout',
    '/dashboard': 'Customer/Dashboard',
    '/admin/dashboard': 'Admin/Dashboard',
    '/admin/restaurants': 'Admin/Restaurants/Index',
    '/admin/restaurants/create': 'Admin/Restaurants/Create',
    '/admin/orders': 'Admin/Orders/Index',
    '/admin/customers': 'Admin/Customers/Index',
    '/admin/users': 'Admin/Users/Index',
    '/admin/users/create': 'Admin/Users/Create',
    '/admin/delivery-drivers': 'Admin/DeliveryDrivers/Index',
    '/admin/delivery-drivers/create': 'Admin/DeliveryDrivers/Create',
    '/admin/finance': 'Admin/Finance/Overview',
    '/admin/finance/revenue': 'Admin/Finance/Revenue',
    '/admin/finance/expenses': 'Admin/Finance/Expenses',
    '/admin/finance/profit-loss': 'Admin/Finance/ProfitLoss',
    '/admin/invoices': 'Admin/Invoices/Index',
    '/admin/invoices/create': 'Admin/Invoices/Create',
    '/admin/collections': 'Admin/Collections/Index',
    '/admin/billing': 'Admin/Billing/Hub',
    '/admin/analytics': 'Admin/Analytics/Index',
    '/admin/activity-logs': 'Admin/ActivityLogs/Index',
    '/admin/backups': 'Admin/Backups/Index',
    '/admin/roles': 'Admin/Roles/Index',
    '/admin/settings': 'Admin/Settings/Index',
    '/restaurant/dashboard': 'Restaurant/Dashboard',
    '/restaurant/orders': 'Restaurant/Orders/Index',
    '/restaurant/menu': 'Restaurant/Menu/Index',
    '/restaurant/menu/create': 'Restaurant/Menu/Create',
    '/restaurant/categories': 'Restaurant/Categories/Index',
    '/restaurant/offers': 'Restaurant/Offers/Index',
    '/restaurant/offers/create': 'Restaurant/Offers/Create',
    '/restaurant/delivery-drivers': 'Restaurant/DeliveryDrivers/Index',
    '/restaurant/delivery-drivers/create': 'Restaurant/DeliveryDrivers/Create',
    '/restaurant/driver-stats': 'Restaurant/DriverStats/Index',
    '/restaurant/billing': 'Restaurant/Billing/Index',
    '/restaurant/analytics': 'Restaurant/Analytics/Index',
    '/restaurant/settings': 'Restaurant/Settings/Index',
    '/delivery/dashboard': 'Delivery/Dashboard',
    '/delivery/active-order': 'Delivery/ActiveOrder',
    '/delivery/order-history': 'Delivery/OrderHistory',
    '/delivery/orders': 'Delivery/OrderHistory',
    '/delivery/profile': 'Delivery/Profile',
    '/delivery/suspended': 'Delivery/Suspended',
};

const componentPatterns: Array<{ test: RegExp; component: string }> = [
    { test: /^\/restaurants\/[^/]+$/, component: 'Public/RestaurantDetails' },
    { test: /^\/orders\/[^/]+$/, component: 'Customer/OrderDetails' },
    { test: /^\/customer\/orders\/[^/]+$/, component: 'Customer/OrderDetails' },
    { test: /^\/admin\/restaurants\/\d+\/edit$/, component: 'Admin/Restaurants/Edit' },
    { test: /^\/admin\/restaurants\/\d+$/, component: 'Admin/Restaurants/Show' },
    { test: /^\/admin\/orders\/\d+$/, component: 'Admin/Orders/Show' },
    { test: /^\/admin\/customers\/\d+$/, component: 'Admin/Customers/Show' },
    { test: /^\/admin\/users\/\d+\/edit$/, component: 'Admin/Users/Edit' },
    { test: /^\/admin\/invoices\/\d+$/, component: 'Admin/Invoices/Show' },
    { test: /^\/restaurant\/orders\/\d+$/, component: 'Restaurant/Orders/Show' },
    { test: /^\/restaurant\/menu\/\d+\/edit$/, component: 'Restaurant/Menu/Edit' },
];

export const NAVIGATION_CACHE_FOR = ['45s', '10m'] as const;

const QUEUE_LIMIT = 30;
const providers = new Set<() => string[]>();
const queue: string[] = [];

let installed = false;
let active: string | null = null;
let watchdog = 0;

function componentName(href: string): string | undefined {
    const path = href.split('?')[0];

    return hrefComponents[path] ?? componentPatterns.find((pattern) => pattern.test(path))?.component;
}

function preloadComponent(href: string): void {
    const name = componentName(href);
    const loader = name ? pageModules[`../Pages/${name}.vue`] : undefined;

    if (typeof loader === 'function') {
        void loader();
    }
}

function isFresh(href: string): boolean {
    const cached = router.getCached(href);

    return cached !== null && !cached.inFlight && cached.staleTimestamp > Date.now();
}

function here(): string {
    const path = window.location.pathname.replace(/\/$/, '') || '/';

    return path + window.location.search;
}

function normalizeHref(raw: string | null): string | null {
    if (!raw || raw.startsWith('#') || raw.startsWith('mailto:') || raw.startsWith('tel:') || raw.startsWith('javascript:')) {
        return null;
    }

    let url: URL;

    try {
        url = new URL(raw, window.location.origin);
    } catch {
        return null;
    }

    if (url.origin !== window.location.origin) {
        return null;
    }

    const path = url.pathname.replace(/\/$/, '') || '/';

    if (
        path.endsWith('.pdf')
        || path.includes('/availability-statuses')
        || path.includes('/driver-location')
        || path === '/logout'
    ) {
        return null;
    }

    return path + url.search;
}

function hrefFromVisit(visitUrl: URL | string): string | null {
    const url = visitUrl instanceof URL ? visitUrl : new URL(String(visitUrl), window.location.origin);
    const path = url.pathname.replace(/\/$/, '') || '/';

    return path + url.search;
}

function remember(hrefs: string[], front = false): void {
    const next = hrefs.filter((href) => href !== here() && href !== active && !queue.includes(href));

    if (front) {
        queue.unshift(...next);
    } else {
        queue.push(...next);
    }

    if (queue.length > QUEUE_LIMIT) {
        queue.splice(QUEUE_LIMIT);
    }
}

function clearWatchdog(): void {
    window.clearTimeout(watchdog);
    watchdog = 0;
}

function finishActive(): void {
    clearWatchdog();
    active = null;
    pump();
}

function armWatchdog(): void {
    clearWatchdog();
    watchdog = window.setTimeout(finishActive, 6000);
}

function pump(): void {
    if (active) {
        return;
    }

    while (queue.length > 0) {
        const href = queue.shift();

        if (!href || href === here() || isFresh(href)) {
            continue;
        }

        preloadComponent(href);
        active = href;
        armWatchdog();
        router.prefetch(href, {}, { cacheFor: [...NAVIGATION_CACHE_FOR] });

        return;
    }
}

function collect(): string[] {
    const fromProviders = [...providers].flatMap((resolve) => resolve());
    const fromDom = [...document.querySelectorAll('a[href]')].map((link) => link.getAttribute('href'));

    return [...new Set([...fromProviders, ...fromDom].map((href) => normalizeHref(href)).filter((href): href is string => href !== null))];
}

function schedule(): void {
    const wanted = collect().filter((href) => href !== here() && href !== active && !isFresh(href));
    const kept = queue.filter((href) => wanted.includes(href));
    const added = wanted.filter((href) => !kept.includes(href));

    queue.splice(0, queue.length, ...[...kept, ...added].slice(0, QUEUE_LIMIT));
    pump();
}

function prioritize(href: string): void {
    preloadComponent(href);

    if (href === here() || href === active || isFresh(href)) {
        return;
    }

    const rest = queue.filter((item) => item !== href);
    queue.splice(0, queue.length, href, ...rest);

    if (active) {
        router.prefetch(href, {}, { cacheFor: [...NAVIGATION_CACHE_FOR] });

        return;
    }

    pump();
}

function onIntent(event: Event): void {
    const target = event.target;

    if (!(target instanceof Element)) {
        return;
    }

    const link = target.closest('a[href]');

    if (!(link instanceof HTMLAnchorElement) || link.target === '_blank' || link.hasAttribute('download')) {
        return;
    }

    const href = normalizeHref(link.getAttribute('href'));

    if (href) {
        prioritize(href);
    }
}

function install(): void {
    if (installed) {
        return;
    }

    installed = true;

    router.on('prefetching', (event) => {
        const href = hrefFromVisit(event.detail.visit.url);

        if (!href) {
            return;
        }

        if (active && active !== href && !isFresh(active)) {
            remember([active], true);
        }

        active = href;
        armWatchdog();
    });

    router.on('prefetched', (event) => {
        const href = hrefFromVisit(event.detail.visit.url);

        if (href === active) {
            finishActive();
        }
    });

    router.on('navigate', () => {
        window.setTimeout(schedule, 0);
        window.setTimeout(schedule, 80);
    });

    document.addEventListener('pointerdown', onIntent, true);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            schedule();
        }
    });
    window.addEventListener('app:navigation-cache-flushed', schedule);
    window.setInterval(schedule, 60000);
}

export function warmNavigation(resolveHrefs: () => string[]): void {
    onMounted(() => {
        install();
        providers.add(resolveHrefs);
        schedule();
    });

    onBeforeUnmount(() => {
        providers.delete(resolveHrefs);
    });
}
