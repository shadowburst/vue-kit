<script setup lang="ts">
import { DropdownMenuLabel } from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import { useTailwindBreakpoints } from '@/composables';
import type { DropdownMenuLabelProps, LabelProps } from 'reka-ui';
import { useForwardProps } from 'reka-ui';

type Props = DropdownMenuLabelProps & LabelProps;
const props = defineProps<Props>();

const forwardedDropdownMenuProps = useForwardProps(props as DropdownMenuLabelProps);
const forwardedLabelProps = useForwardProps(props as LabelProps);

const { md } = useTailwindBreakpoints();
</script>

<template>
    <DropdownMenuLabel v-if="md" v-bind="forwardedDropdownMenuProps">
        <slot />
    </DropdownMenuLabel>
    <Label v-else v-bind="forwardedLabelProps">
        <slot />
    </Label>
</template>
