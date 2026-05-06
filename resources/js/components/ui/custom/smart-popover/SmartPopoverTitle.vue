<script setup lang="ts">
import { DrawerTitle } from '@/components/ui/drawer';
import { cn } from '@/lib/utils';
import { reactiveOmit } from '@vueuse/core';
import { useForwardProps } from 'reka-ui';
import { DrawerTitleProps } from 'vaul-vue';
import { HTMLAttributes } from 'vue';
import { injectSmartPopoverContext } from './SmartPopover.vue';

type Props = DrawerTitleProps & {
    class?: HTMLAttributes['class'];
};
const props = defineProps<Props>();

const forwardedDrawerProps = useForwardProps(reactiveOmit(props, 'class') as DrawerTitleProps);

const { isDesktop } = injectSmartPopoverContext();
</script>

<template>
    <div v-if="isDesktop" data-slot="popover-title" :class="cn('text-sm', props.class)">
        <slot />
    </div>
    <DrawerTitle v-else v-bind="forwardedDrawerProps" :class="cn('text-primary text-lg font-semibold', props.class)">
        <slot />
    </DrawerTitle>
</template>
