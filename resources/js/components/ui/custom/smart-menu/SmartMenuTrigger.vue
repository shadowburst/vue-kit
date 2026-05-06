<script setup lang="ts">
import { DrawerTrigger } from '@/components/ui/drawer';
import { DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useTailwindBreakpoints } from '@/composables';
import type { DropdownMenuTriggerProps } from 'reka-ui';
import { useForwardProps } from 'reka-ui';
import type { DrawerTriggerProps } from 'vaul-vue';

type Props = DropdownMenuTriggerProps & DrawerTriggerProps;
const props = defineProps<Props>();

const forwardedDropdownMenuProps = useForwardProps(props as DropdownMenuTriggerProps);
const forwardedDrawerProps = useForwardProps(props as DrawerTriggerProps);

const { md } = useTailwindBreakpoints();
</script>

<template>
    <DropdownMenuTrigger v-if="md" v-bind="forwardedDropdownMenuProps">
        <slot />
    </DropdownMenuTrigger>
    <DrawerTrigger v-else v-bind="forwardedDrawerProps">
        <slot />
    </DrawerTrigger>
</template>
