<script setup lang="ts">
import { DialogTitle } from '@/components/ui/dialog';
import { DrawerTitle } from '@/components/ui/drawer';
import { useTailwindBreakpoints } from '@/composables';
import type { DialogTitleProps } from 'reka-ui';
import { useForwardProps } from 'reka-ui';
import type { DrawerTitleProps } from 'vaul-vue';

type Props = DialogTitleProps & DrawerTitleProps;
const props = defineProps<Props>();

const forwardedDialogProps = useForwardProps(props as DialogTitleProps);
const forwardedDrawerProps = useForwardProps(props as DrawerTitleProps);

const { md } = useTailwindBreakpoints();
</script>

<template>
    <DialogTitle v-if="md" v-bind="forwardedDialogProps">
        <slot />
    </DialogTitle>
    <DrawerTitle v-else v-bind="forwardedDrawerProps">
        <slot />
    </DrawerTitle>
</template>
