const DEFAULT_FALLBACK = '/images/sandwich-foul.jpg';

export function resolveMediaUrl(
    path: string | null | undefined,
    fallback: string = DEFAULT_FALLBACK,
): string {
    if (!path) {
        return fallback;
    }

    if (
        path.startsWith('http://')
        || path.startsWith('https://')
        || path.startsWith('/')
        || path.startsWith('blob:')
        || path.startsWith('data:')
    ) {
        return path;
    }

    return `/storage/${path}`;
}
