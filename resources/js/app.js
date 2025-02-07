import './bootstrap';
import '../css/app.css';
import 'sweetalert2/dist/sweetalert2.min.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import DataTablesLib from 'datatables.net'; 
import DataTable from 'datatables.net-vue3';
import VueSweetalert2 from 'vue-sweetalert2';

DataTable.use(DataTablesLib);

const appName = import.meta.env.VITE_APP_NAME || 'WarePro';

createInertiaApp({
    title: (title) => `${appName} - ${title}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(VueSweetalert2, {
                confirmButtonColor: '#41b882',
                cancelButtonColor: '#ff7674',
              },
            )
            .component('DataTable', DataTable)
            .mount(el);
    },
    progress: {
        color: 'purple',
        showSpinner: true,
    },
});
