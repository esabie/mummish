/**
 * Tiny pub/sub for global HTTP error toasts (Inertia + axios).
 */

const listeners = new Set();

/**
 * @param {{ message: string, status?: number|null }} payload
 */
export function showHttpError(payload) {
    const message = typeof payload === 'string' ? payload : payload?.message;
    if (!message) {
        return;
    }

    const detail = {
        message,
        status: typeof payload === 'object' ? (payload.status ?? null) : null,
        id: Date.now() + Math.random(),
    };

    listeners.forEach((listener) => listener(detail));
}

/**
 * @param {(payload: { message: string, status: number|null, id: number }) => void} listener
 * @returns {() => void}
 */
export function subscribeHttpErrors(listener) {
    listeners.add(listener);

    return () => {
        listeners.delete(listener);
    };
}
