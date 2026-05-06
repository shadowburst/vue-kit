<script setup lang="ts">
import { DrawerContent } from '@/components/ui/drawer';
import { DropdownMenuContent } from '@/components/ui/dropdown-menu';
import { useTailwindBreakpoints } from '@/composables';
import { reactiveOmit } from '@vueuse/core';
import type { DialogContentProps, DropdownMenuContentProps } from 'reka-ui';
import { useForwardProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';

type Props = DropdownMenuContentProps &
    DialogContentProps & {
        class?: HTMLAttributes['class'];
    };
const props = defineProps<Props>();

const forwarded = useForwardProps(reactiveOmit(props, 'class'));
const forwardedDropdownMenuProps = useForwardProps(forwarded as DropdownMenuContentProps);
const forwardedDrawerProps = useForwardProps(forwarded as DialogContentProps);

const { md } = useTailwindBreakpoints();
</script>

<template>
    <DropdownMenuContent v-if="md" v-bind="forwardedDropdownMenuProps" :class="props.class">
        <slot />
    </DropdownMenuContent>
    <DrawerContent v-else v-bind="forwardedDrawerProps" :class="props.class">
        <slot />
    </DrawerContent>
</template>
