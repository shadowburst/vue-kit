<script setup lang="ts">
import { DrawerTitle } from '@/components/ui/drawer';
import { DropdownMenuLabel } from '@/components/ui/dropdown-menu';
import { useTailwindBreakpoints } from '@/composables';
import { reactiveOmit } from '@vueuse/core';
import type { DropdownMenuLabelProps } from 'reka-ui';
import { useForwardProps } from 'reka-ui';
import type { DrawerTitleProps } from 'vaul-vue';
import type { HTMLAttributes } from 'vue';

type Props = DropdownMenuLabelProps &
    DrawerTitleProps & {
        class?: HTMLAttributes['class'];
    };
const props = defineProps<Props>();

const forwardedDropdownMenuProps = useForwardProps(reactiveOmit(props, 'class') as DropdownMenuLabelProps);
const forwardedDrawerProps = useForwardProps(reactiveOmit(props, 'class') as DrawerTitleProps);

const { md } = useTailwindBreakpoints();
</script>

<template>
    <DropdownMenuLabel v-if="md" v-bind="forwardedDropdownMenuProps" :class="props.class">
        <slot />
    </DropdownMenuLabel>
    <DrawerTitle v-else v-bind="forwardedDrawerProps" :class="props.class">
        <slot />
    </DrawerTitle>
</template>
