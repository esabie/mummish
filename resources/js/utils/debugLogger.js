export const debugLogger = {
    info(scope, message, meta) {
        if (import.meta.env.DEV) {
            console.info(`[${scope}]`, message, meta ?? '');
        }
    },
    error(scope, message, meta) {
        console.error(`[${scope}]`, message, meta ?? '');
    },
};
