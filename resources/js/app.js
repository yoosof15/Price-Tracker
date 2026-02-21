import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'نرخ‌نامه قیمت';

// SEO: Add preconnect for external resources
const preconnectLinks = [
    { rel: 'preconnect', href: 'https://fonts.bunny.net' },
    { rel: 'dns-prefetch', href: 'https://fonts.bunny.net' },
];

preconnectLinks.forEach(link => {
    const element = document.createElement('link');
    Object.assign(element, link);
    document.head.appendChild(element);
});

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
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#7d00fc',
    },
});
