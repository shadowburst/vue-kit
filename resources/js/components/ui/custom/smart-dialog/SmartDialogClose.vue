<script setup lang="ts">
import { DialogClose } from '@/components/ui/dialog';
import { DrawerClose } from '@/components/ui/drawer';
import { useTailwindBreakpoints } from '@/composables';
import type { DialogCloseProps } from 'reka-ui';
import { useForwardProps } from 'reka-ui';
import type { DrawerCloseProps } from 'vaul-vue';

type Props = DialogCloseProps & DrawerCloseProps;
const props = defineProps<Props>();

const forwardedDialogProps = useForwardProps(props as DialogCloseProps);
const forwardedDrawerProps = useForwardProps(props as DrawerCloseProps);

const { md } = useTailwindBreakpoints();
</script>

<template>
    <DialogClose v-if="md" v-bind="forwardedDialogProps">
        <slot />
    </DialogClose>
    <DrawerClose v-else v-bind="forwardedDrawerProps">
        <slot />
    </DrawerClose>
</template>
