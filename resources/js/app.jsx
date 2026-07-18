import './bootstrap';
import '../css/app.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { Fragment } from 'react';
import NavigationLoader from './Components/NavigationLoader';
import CartDrawer from './Components/CartDrawer';
import VendorCartConflictModal from './Components/VendorCartConflictModal';
import { CartProvider } from './context/CartContext';
import { debugLogger } from './utils/debugLogger';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

router.on('invalid', (event) => {
    if (event.detail.response?.status === 419) {
        event.preventDefault();
        window.location.reload();
    }
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
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
