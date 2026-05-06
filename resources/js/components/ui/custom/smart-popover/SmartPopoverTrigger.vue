<script setup lang="ts">
import { DrawerTrigger } from '@/components/ui/drawer';
import { PopoverTrigger } from '@/components/ui/popover';
import { PopoverTriggerProps, useForwardProps } from 'reka-ui';
import { DrawerTriggerProps } from 'vaul-vue';
import { injectSmartPopoverContext } from './SmartPopover.vue';

type Props = PopoverTriggerProps & DrawerTriggerProps;
const props = defineProps<Props>();

const forwardedPopoverProps = useForwardProps(props as PopoverTriggerProps);
const forwardedDrawerProps = useForwardProps(props as DrawerTriggerProps);

const { isDesktop } = injectSmartPopoverContext();
</script>

<template>
    <PopoverTrigger v-if="isDesktop" v-bind="forwardedPopoverProps">
        <slot />
    </PopoverTrigger>
    <DrawerTrigger v-else v-bind="forwardedDrawerProps">
        <slot />
    </DrawerTrigger>
</template>
