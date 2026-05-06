<script setup lang="ts">
import { DrawerContent } from '@/components/ui/drawer';
import { DropdownMenuContent } from '@/components/ui/dropdown-menu';
import { reactiveOmit } from '@vueuse/core';
import { DialogContentProps, DropdownMenuContentProps, Primitive, useForwardProps } from 'reka-ui';
import { HTMLAttributes } from 'vue';
import { injectSmartMenuContext } from './SmartMenu.vue';

type Props = DropdownMenuContentProps &
    DialogContentProps & {
        class?: HTMLAttributes['class'];
    };
const props = defineProps<Props>();

const forwarded = useForwardProps(reactiveOmit(props, 'as', 'asChild', 'class'));
const forwardedDropdownMenuProps = useForwardProps(forwarded as DropdownMenuContentProps);
const forwardedDrawerProps = useForwardProps(forwarded as DialogContentProps);

const { isDesktop } = injectSmartMenuContext();
</script>

<template>
    <DropdownMenuContent v-if="isDesktop" v-bind="forwardedDropdownMenuProps" :class="props.class">
        <slot />
    </DropdownMenuContent>
    <DrawerContent v-else v-bind="forwardedDrawerProps" :class="props.class">
        <Primitive :as :as-child class="flex h-full flex-col space-y-1 overflow-y-auto px-2 py-1.5">
            <slot />
        </Primitive>
    </DrawerContent>
</template>
