<script lang="ts">
type ConfirmDialogState = {
    variant: 'default' | 'destructive';
    title?: string;
    description?: string;
    footnote?: string;
    callback?: () => void;
};

type ConfirmDialogContext = {
    confirm: (state: Partial<ConfirmDialogState>) => void;
};

export const [injectConfirmDialogContext, provideConfirmDialogContext] =
    createContext<ConfirmDialogContext>('ConfirmDialog');
</script>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    SmartDialog,
    SmartDialogClose,
    SmartDialogContent,
    SmartDialogDescription,
    SmartDialogFooter,
    SmartDialogHeader,
    SmartDialogTitle,
} from '@/components/ui/custom/smart-dialog';
import { upperFirst } from 'es-toolkit';
import { createContext } from 'reka-ui';
import { reactive, ref } from 'vue';

const open = ref<boolean>(false);

const state = reactive<ConfirmDialogState>({
    variant: 'default',
});

function confirm({ variant = 'default', ...options }: Partial<ConfirmDialogState>) {
    state.variant = variant;
    state.title = options.title;
    state.description = options.description;
    state.footnote = options.footnote;
    state.callback = options.callback;
    open.value = true;
}

provideConfirmDialogContext({
    confirm,
});
</script>

<template>
    <SmartDialog v-model:open="open">
        <slot />
        <SmartDialogContent>
            <SmartDialogHeader>
                <SmartDialogTitle>
                    {{ state.title ?? $t(`components.ui.custom.confirm_dialog.title.${state.variant}`) }}
                </SmartDialogTitle>
                <SmartDialogDescription v-if="state.description">
                    {{ state.description }}
                </SmartDialogDescription>
                <SmartDialogDescription class="text-xs italic" v-if="state.footnote">
                    {{ state.footnote }}
                </SmartDialogDescription>
            </SmartDialogHeader>
            <SmartDialogFooter>
                <SmartDialogClose as-child>
                    <Button v-if="state.callback" :variant="state.variant" @click="state.callback()">
                        {{ upperFirst($t('confirm')) }}
                    </Button>
                </SmartDialogClose>
                <SmartDialogClose as-child>
                    <Button variant="ghost">
                        {{ upperFirst($t('cancel')) }}
                    </Button>
                </SmartDialogClose>
            </SmartDialogFooter>
        </SmartDialogContent>
    </SmartDialog>
</template>
