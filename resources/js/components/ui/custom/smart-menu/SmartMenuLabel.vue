<script setup lang="ts">
import { DropdownMenuLabel } from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import { DropdownMenuLabelProps, LabelProps, useForwardProps } from 'reka-ui';
import { injectSmartMenuContext } from './SmartMenu.vue';

type Props = DropdownMenuLabelProps & LabelProps;
const props = defineProps<Props>();

const forwardedDropdownMenuProps = useForwardProps(props as DropdownMenuLabelProps);
const forwardedLabelProps = useForwardProps(props as LabelProps);

const { isDesktop } = injectSmartMenuContext();
</script>

<template>
    <DropdownMenuLabel v-if="isDesktop" v-bind="forwardedDropdownMenuProps">
        <slot />
    </DropdownMenuLabel>
    <Label v-else v-bind="forwardedLabelProps">
        <slot />
    </Label>
</template>
