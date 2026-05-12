<script setup lang="ts">
import { DialogDescription } from '@/components/ui/dialog';
import { DrawerDescription } from '@/components/ui/drawer';
import { useTailwindBreakpoints } from '@/composables';
import type { DialogDescriptionProps } from 'reka-ui';
import { useForwardProps } from 'reka-ui';
import type { DrawerDescriptionProps } from 'vaul-vue';

type Props = DialogDescriptionProps & DrawerDescriptionProps;
const props = defineProps<Props>();

const forwardedDialogProps = useForwardProps(props as DialogDescriptionProps);
const forwardedDrawerProps = useForwardProps(props as DrawerDescriptionProps);

const { md } = useTailwindBreakpoints();
</script>

<template>
    <DialogDescription v-if="md" v-bind="forwardedDialogProps">
        <slot />
    </DialogDescription>
    <DrawerDescription v-else v-bind="forwardedDrawerProps">
        <slot />
    </DrawerDescription>
</template>
