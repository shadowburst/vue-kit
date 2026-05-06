<script setup lang="ts">
import { DialogTrigger } from '@/components/ui/dialog';
import { DrawerTrigger } from '@/components/ui/drawer';
import { useTailwindBreakpoints } from '@/composables';
import type { DialogTriggerProps } from 'reka-ui';
import { useForwardProps } from 'reka-ui';
import type { DrawerTriggerProps } from 'vaul-vue';

type Props = DialogTriggerProps & DrawerTriggerProps;
const props = defineProps<Props>();

const forwardedDialogProps = useForwardProps(props as DialogTriggerProps);
const forwardedDrawerProps = useForwardProps(props as DrawerTriggerProps);

const { md } = useTailwindBreakpoints();
</script>

<template>
    <DialogTrigger v-if="md" v-bind="forwardedDialogProps">
        <slot />
    </DialogTrigger>
    <DrawerTrigger v-else v-bind="forwardedDrawerProps">
        <slot />
    </DrawerTrigger>
</template>
