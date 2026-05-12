<script setup lang="ts">
import { DrawerTrigger } from '@/components/ui/drawer';
import { PopoverTrigger } from '@/components/ui/popover';
import { useTailwindBreakpoints } from '@/composables';
import type { PopoverTriggerProps } from 'reka-ui';
import { useForwardProps } from 'reka-ui';
import type { DrawerTriggerProps } from 'vaul-vue';

type Props = PopoverTriggerProps & DrawerTriggerProps;
const props = defineProps<Props>();

const forwardedPopoverProps = useForwardProps(props as PopoverTriggerProps);
const forwardedDrawerProps = useForwardProps(props as DrawerTriggerProps);

const { md } = useTailwindBreakpoints();
</script>

<template>
    <PopoverTrigger v-if="md" v-bind="forwardedPopoverProps">
        <slot />
    </PopoverTrigger>
    <DrawerTrigger v-else v-bind="forwardedDrawerProps">
        <slot />
    </DrawerTrigger>
</template>
