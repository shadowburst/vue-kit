<script setup lang="ts">
import { DrawerDescription } from '@/components/ui/drawer';
import { cn } from '@/lib/utils';
import { reactiveOmit } from '@vueuse/core';
import { useForwardProps } from 'reka-ui';
import { DrawerDescriptionProps } from 'vaul-vue';
import { HTMLAttributes } from 'vue';
import { injectSmartPopoverContext } from './SmartPopover.vue';

type Props = DrawerDescriptionProps & {
    class?: HTMLAttributes['class'];
};
const props = defineProps<Props>();

const forwardedDrawerProps = useForwardProps(reactiveOmit(props, 'class') as DrawerDescriptionProps);

const { isDesktop } = injectSmartPopoverContext();
</script>

<template>
    <p v-if="isDesktop" data-slot="popover-description" :class="cn('text-muted-foreground text-xs', props.class)">
        <slot />
    </p>
    <DrawerDescription v-else v-bind="forwardedDrawerProps" :class="props.class">
        <slot />
    </DrawerDescription>
</template>
