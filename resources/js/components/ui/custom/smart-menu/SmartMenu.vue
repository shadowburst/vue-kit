<script setup lang="ts">
import { Drawer } from '@/components/ui/drawer';
import { DropdownMenu } from '@/components/ui/dropdown-menu';
import { useTailwindBreakpoints } from '@/composables';
import type { DialogRootEmits, DropdownMenuRootEmits, DropdownMenuRootProps } from 'reka-ui';
import { useForwardPropsEmits } from 'reka-ui';
import type { DrawerRootProps } from 'vaul-vue';

type Props = DropdownMenuRootProps & DrawerRootProps;
const props = defineProps<Props>();

type Emits = DropdownMenuRootEmits & DialogRootEmits;
const emits = defineEmits<Emits>();

const forwardedDropdownMenuProps = useForwardPropsEmits(props as DropdownMenuRootProps, emits);
const forwardedDrawerProps = useForwardPropsEmits(props as DrawerRootProps, emits);

const isOpen = defineModel<boolean>('open', {
    default: false,
});

const { md } = useTailwindBreakpoints();
</script>

<template>
    <DropdownMenu v-if="md" v-bind="forwardedDropdownMenuProps" v-model:open="isOpen">
        <slot />
    </DropdownMenu>
    <Drawer v-else v-bind="forwardedDrawerProps" v-model:open="isOpen">
        <slot />
    </Drawer>
</template>
