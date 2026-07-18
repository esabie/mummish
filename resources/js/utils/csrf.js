/**
 * Headers for manual fetch/axios calls that need CSRF protection.
 * Prefer the XSRF-TOKEN cookie (updated each response) over the static meta tag.
 */
export function csrfHeaders(extra = {}) {
    const headers = {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
        ...extra,
    };

    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

    if (match) {
        headers['X-XSRF-TOKEN'] = decodeURIComponent(match[1]);
    } else {
        const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (meta) {
            headers['X-CSRF-TOKEN'] = meta;
        }
    }

    return headers;
}
