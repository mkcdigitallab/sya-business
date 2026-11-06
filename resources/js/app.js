import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import StoreLayout from './Layouts/StoreLayout.vue';
import AdminLayout from './Layouts/AdminLayout.vue';

createInertiaApp({
    title: (title) => (title ? `${title} — Siya Business` : 'Siya Business'),

    // Chaque page est chargée à la demande : un visiteur ne télécharge jamais le code de l'administration.
    resolve: async (name) => {
        const page = await resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue'));

        if (page.default.layout === undefined && name !== 'Admin/Login') {
            page.default.layout = name.startsWith('Admin/') ? AdminLayout : StoreLayout;
        }

        return page;
    },

    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) }).use(plugin).mount(el);
    },

    progress: { color: '#C9A346' },
});
