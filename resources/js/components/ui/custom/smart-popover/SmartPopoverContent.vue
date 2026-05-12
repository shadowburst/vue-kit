<script setup lang="ts">
import { DrawerContent } from '@/components/ui/drawer';
import { PopoverContent } from '@/components/ui/popover';
import { useTailwindBreakpoints } from '@/composables';
import { reactiveOmit } from '@vueuse/core';
import type { DialogContentProps, PopoverContentProps } from 'reka-ui';
import { useForwardProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';

type Props = PopoverContentProps &
    DialogContentProps & {
        class?: HTMLAttributes['class'];
    };
const props = defineProps<Props>();

const forwarded = useForwardProps(reactiveOmit(props, 'class'));
const forwardedPopoverProps = useForwardProps(forwarded as PopoverContentProps);
const forwardedDrawerProps = useForwardProps(forwarded as DialogContentProps);

const { md } = useTailwindBreakpoints();
</script>

<template>
    <PopoverContent v-if="md" v-bind="forwardedPopoverProps" :class="props.class">
        <slot />
    </PopoverContent>
    <DrawerContent v-else v-bind="forwardedDrawerProps" :class="props.class">
        <slot />
    </DrawerContent>
</template>
