import type { Page, createHeadManager } from '@inertiajs/core';
import type { router } from '@inertiajs/vue3';
export * from '@/spatie/types';
export * from '@/wayfinder/types';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $inertia: typeof router;
        $page: Page;
        $headManager: ReturnType<typeof createHeadManager>;
    }
}
