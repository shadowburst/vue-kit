<script setup lang="ts">
import { DrawerContent } from '@/components/ui/drawer';
import { PopoverContent } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { reactiveOmit } from '@vueuse/core';
import { DialogContentProps, PopoverContentProps, Primitive, useForwardProps } from 'reka-ui';
import { HTMLAttributes } from 'vue';
import { injectSmartPopoverContext } from './SmartPopover.vue';

type Props = PopoverContentProps &
    DialogContentProps & {
        class?: HTMLAttributes['class'];
    };
const props = defineProps<Props>();

const forwarded = useForwardProps(reactiveOmit(props, 'as', 'asChild', 'class'));
const forwardedPopoverProps = useForwardProps(forwarded as PopoverContentProps);
const forwardedDrawerProps = useForwardProps(forwarded as DialogContentProps);

const { isDesktop } = injectSmartPopoverContext();
</script>

<template>
    <PopoverContent
        v-if="isDesktop"
        v-bind="forwardedPopoverProps"
        :class="cn('grid w-fit max-w-dvw gap-3', props.class)"
    >
        <slot />
    </PopoverContent>
    <DrawerContent v-else v-bind="forwardedDrawerProps" :class="props.class">
        <Primitive :as :as-child class="flex h-full flex-col gap-6 overflow-y-auto p-4">
            <slot />
        </Primitive>
    </DrawerContent>
</template>
