<script setup lang="ts">
import { Drawer } from '@/components/ui/drawer';
import { Popover } from '@/components/ui/popover';
import { useTailwindBreakpoints } from '@/composables';
import type { DialogRootEmits, PopoverRootEmits, PopoverRootProps } from 'reka-ui';
import { useForwardPropsEmits } from 'reka-ui';
import type { DrawerRootProps } from 'vaul-vue';

type Props = PopoverRootProps & DrawerRootProps;
const props = defineProps<Props>();

type Emits = PopoverRootEmits & DialogRootEmits;
const emits = defineEmits<Emits>();

const forwardedPopoverProps = useForwardPropsEmits(props as PopoverRootProps, emits);
const forwardedDrawerProps = useForwardPropsEmits(props as DrawerRootProps, emits);

const isOpen = defineModel<boolean>('open', {
    default: false,
});

const { md } = useTailwindBreakpoints();
</script>

<template>
    <Popover v-if="md" v-bind="forwardedPopoverProps" v-model:open="isOpen">
        <slot />
    </Popover>
    <Drawer v-else v-bind="forwardedDrawerProps" v-model:open="isOpen">
        <slot />
    </Drawer>
</template>
