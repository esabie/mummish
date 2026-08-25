/**
 * User-facing copy for common HTTP failures (uploads, session, server).
 *
 * @param {number|undefined|null} status
 * @param {Error|{message?: string}|null} [error]
 * @returns {string}
 */
export function httpErrorMessage(status, error = null) {
    const code = Number(status);

    if (code === 413) {
        return 'That file is too large to upload. Please use a smaller image (under 2 MB) and try again.';
    }

    if (code === 419) {
        return 'Your session expired. Refreshing the page…';
    }

    if (code === 403) {
        return 'You do not have permission to do that.';
    }

    if (code === 404) {
        return 'We could not find what you were looking for.';
    }

    if (code === 422) {
        return 'Please check the form and fix any highlighted fields.';
    }

    if (code === 429) {
        return 'Too many attempts. Please wait a moment and try again.';
    }

    if (code >= 500 && code < 600) {
        return 'Something went wrong on our side. Please try again in a moment.';
    }

    if (!code || code === 0) {
        const message = String(error?.message ?? '').toLowerCase();
        if (message.includes('network') || message.includes('failed to fetch')) {
            return 'Network error. Check your connection and try again.';
        }

        return 'Something went wrong. Please try again.';
    }

    return 'Something went wrong. Please try again.';
}
