<script lang="ts">
import type { ReloadOptions } from '@inertiaui/modal-vue';
import { createContext } from 'reka-ui';
import type { ComputedRef } from 'vue';

export type InertiaModalContext = {
    close: () => void;
    reload: (options?: ReloadOptions) => void;
    isOpen: ComputedRef<boolean>;
    onTopOfStack: ComputedRef<boolean>;
};

export const [injectInertiaModalContext, provideInertiaModalContext] =
    createContext<InertiaModalContext>('InertiaModal');
</script>

<script setup lang="ts">
import { SmartDialog, SmartDialogContent } from '@/components/ui/custom/smart-dialog';
import { HeadlessModal } from '@inertiaui/modal-vue';
import { computed, ref } from 'vue';

type HeadlessModalInstance = {
    isOpen: boolean;
    onTopOfStack: boolean;
    close: () => void;
    reload: (options?: ReloadOptions) => void;
};

const headless = ref<HeadlessModalInstance | null>(null);

const isOpen = computed<boolean>(() => Boolean(headless.value?.isOpen));
const onTopOfStack = computed<boolean>(() => Boolean(headless.value?.onTopOfStack));

function close() {
    headless.value?.close();
}

function reload(options?: ReloadOptions) {
    headless.value?.reload(options);
}

provideInertiaModalContext({ close, reload, isOpen, onTopOfStack });

const open = computed<boolean>({
    get: () => isOpen.value,
    set: (value) => {
        if (!value) {
            close();
        }
    },
});
</script>

<template>
    <HeadlessModal ref="headless">
        <SmartDialog v-model:open="open">
            <SmartDialogContent>
                <slot />
            </SmartDialogContent>
        </SmartDialog>
    </HeadlessModal>
</template>
