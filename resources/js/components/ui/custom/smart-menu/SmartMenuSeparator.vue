<script setup lang="ts">
import { DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { useTailwindBreakpoints } from '@/composables';
import type { DropdownMenuSeparatorProps, SeparatorProps } from 'reka-ui';
import { useForwardProps } from 'reka-ui';

type Props = DropdownMenuSeparatorProps & SeparatorProps;
const props = defineProps<Props>();

const forwardedDropdownMenuProps = useForwardProps(props as DropdownMenuSeparatorProps);
const forwardedSeparatorProps = useForwardProps(props as SeparatorProps);

const { md } = useTailwindBreakpoints();
</script>

<template>
    <DropdownMenuSeparator v-if="md" v-bind="forwardedDropdownMenuProps">
        <slot />
    </DropdownMenuSeparator>
    <Separator v-else v-bind="forwardedSeparatorProps">
        <slot />
    </Separator>
</template>
