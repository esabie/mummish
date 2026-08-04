import './bootstrap';
import '../css/app.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { Fragment } from 'react';
import NavigationLoader from './Components/NavigationLoader';
import CartDrawer from './Components/CartDrawer';
import VendorCartConflictModal from './Components/VendorCartConflictModal';
import HttpErrorToast from './Components/HttpErrorToast';
import { CartProvider } from './context/CartContext';
import { debugLogger } from './utils/debugLogger';
import { showHttpError } from './utils/httpErrorBus';
import { httpErrorMessage } from './utils/httpErrorMessage';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const siteTitle = 'The Mummish';

router.on('invalid', (event) => {
    const status = event.detail.response?.status;
    const message = httpErrorMessage(status);

    debugLogger.error('Inertia', 'Non-Inertia response', { status });

    // Always take over the default modal so users see a clear message.
    event.preventDefault();
    showHttpError({ message, status });

    if (status === 419) {
        window.setTimeout(() => window.location.reload(), 1200);
    }
});

router.on('exception', (event) => {
    debugLogger.error('Inertia', 'Visit exception', {
        exception: event.detail.exception,
    });
    event.preventDefault();
    showHttpError({
        message: httpErrorMessage(0, event.detail.exception),
        status: 0,
    });
});

createInertiaApp({
    title: (title) => {
        if (!title || title === appName || title === siteTitle) {
            return siteTitle;
        }

        // Already includes the brand — don't append again (avoids "The Mummish - Mummish").
        if (/\bMummish\b/i.test(title)) {
            return title;
        }

        return `${title} - ${siteTitle}`;
    },
    resolve: (name) => {
        debugLogger.info('Inertia', 'Resolving page component', { name });
        return resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx'));
    },
    setup({ el, App, props }) {
        debugLogger.info('Inertia', 'App setup started', {
            component: props?.initialPage?.component,
            url: props?.initialPage?.url,
        });
        const root = createRoot(el);

        root.render(
            <CartProvider>
                <Fragment>
                    <NavigationLoader />
                    <HttpErrorToast />
                    <App {...props} />
                    <CartDrawer />
                    <VendorCartConflictModal />
                </Fragment>
            </CartProvider>
        );
    },
    progress: {
        delay: 0,
        color: '#0f766e',
        includeCSS: true,
        showSpinner: true,
    },
});

window.addEventListener('error', (event) => {
    debugLogger.error('Window', 'Unhandled error', {
        message: event.message,
        source: event.filename,
        line: event.lineno,
        column: event.colno,
    });
});

window.addEventListener('unhandledrejection', (event) => {
    debugLogger.error('Window', 'Unhandled promise rejection', {
        reason: event.reason,
    });
});
