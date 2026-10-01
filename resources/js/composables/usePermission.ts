import { usePage } from '@inertiajs/vue3';
import type { SharedInertiaProps } from '../Types';

export function userCan(
    role: string | null | undefined,
    permissions: string[] | null | undefined,
    permission: string,
): boolean {
    if (!role) {
        return false;
    }

    if (role === 'SUPER_ADMIN') {
        return true;
    }

    return (permissions ?? []).includes(permission);
}

export function usePermission(): { can: (permission: string) => boolean } {
    const page = usePage<SharedInertiaProps>();

    const can = (permission: string): boolean => userCan(
        page.props.auth.user?.role,
        page.props.auth.permissions,
        permission,
    );

    return { can };
}
