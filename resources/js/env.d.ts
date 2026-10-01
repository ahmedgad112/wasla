/// <reference types="vite/client" />

import type { SharedProps } from './Types';

declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>;
    export default component;
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $can: (permission: string) => boolean;
        $page: { props: SharedProps };
    }
}
