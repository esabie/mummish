import axios from 'axios';
import { showHttpError } from './utils/httpErrorBus';
import { httpErrorMessage } from './utils/httpErrorMessage';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;

        const shouldToast =
            !error.response ||
            status === 413 ||
            status === 419 ||
            status === 429 ||
            (status >= 500 && status < 600);

        if (shouldToast) {
            showHttpError({
                message: httpErrorMessage(status, error),
                status: status ?? 0,
            });
        }

        return Promise.reject(error);
    },
);
