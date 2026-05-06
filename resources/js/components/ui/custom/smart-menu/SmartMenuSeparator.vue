<script setup lang="ts">
import { DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { DropdownMenuSeparatorProps, SeparatorProps, useForwardProps } from 'reka-ui';
import { injectSmartMenuContext } from './SmartMenu.vue';

type Props = DropdownMenuSeparatorProps & SeparatorProps;
const props = defineProps<Props>();

const forwardedDropdownMenuProps = useForwardProps(props as DropdownMenuSeparatorProps);
const forwardedSeparatorProps = useForwardProps(props as SeparatorProps);

const { isDesktop } = injectSmartMenuContext();
</script>

<template>
    <DropdownMenuSeparator v-if="isDesktop" v-bind="forwardedDropdownMenuProps">
        <slot />
    </DropdownMenuSeparator>
    <Separator v-else v-bind="forwardedSeparatorProps">
        <slot />
    </Separator>
</template>
