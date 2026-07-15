import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
import * as Sentry from '@sentry/vue'

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
        return pages[`./Pages/${name}.vue`]
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })

        // Error reporting (Sentry) — no-op kalau VITE_SENTRY_DSN kosong
        if (import.meta.env.VITE_SENTRY_DSN) {
            Sentry.init({
                app,
                dsn: import.meta.env.VITE_SENTRY_DSN,
                environment: import.meta.env.MODE,
            });
        }

        app
        // Set mixins
        .mixin({
            methods: {
                examTimeRangeChecker(start_time, end_time) {
                    return new Date() >= new Date(start_time) && new Date() <= new Date(end_time);
                },
                examTimeStartChecker(start_time) {
                    return new Date() < new Date(start_time);
                },
                examTimeEndChecker(end_time) {
                    return new Date() > new Date(end_time);
                }
            }
        })
        .component('QuillEditor', QuillEditor) // Register Quill globally
        .use(plugin)
        .mount(el)
    },
    progress: {
        delay: 250,
        color: '#29d',
        includeCSS: true,
        showSpinner: false
    }
});