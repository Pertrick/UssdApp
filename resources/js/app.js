import '../css/app.css';
import './bootstrap';
import './utils/csrf';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pinia = createPinia();

// Toast configuration
const toastOptions = {
    position: 'top-right',
    timeout: 5000,
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: true,
    draggablePercent: 0.6,
    showCloseButtonOnHover: false,
    hideProgressBar: false,
    closeButton: 'button',
    icon: true,
    rtl: false,
    transition: 'Vue-Toastification__bounce',
    maxToasts: 20,
    newestOnTop: true,
    filterBeforeCreate: (toast, toasts) => {
        if (toasts.filter(t => t.type === toast.type).length !== 0) {
            return false;
        }
        return toast;
    },
};

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue)
            .use(Toast, toastOptions)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
    // Add CSRF token handling
    beforeRender: (callback) => {
        // Ensure CSRF token is available
        if (document.querySelector('meta[name="csrf-token"]')) {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            if (token) {
                window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
            }
        }
        callback();
    },
});
