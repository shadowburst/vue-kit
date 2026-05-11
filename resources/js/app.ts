import { ModalRoot } from '@/components/ui/custom/inertia-modal';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import { createInertiaApp } from '@inertiajs/vue3';
import { i18nVue } from 'laravel-vue-i18n';
import { createApp, h } from 'vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
    setup({ el, App, props, plugin }) {
        if (!el) {
            return;
        }

        let mounted = false;
        const app = createApp({ render: () => h(ModalRoot, () => h(App, props)) })
            .use(plugin)
            .use(i18nVue, {
                lang: (props.initialPage.props as Record<string, unknown>).locale as string,
                resolve: async (lang: string) => {
                    const langs = import.meta.glob('../../lang/*.json');

                    return await langs[`../../lang/${lang}.json`]();
                },
                onLoad: () => {
                    if (mounted) {
                        return;
                    }

                    mounted = true;
                    app.mount(el);
                },
            });
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
