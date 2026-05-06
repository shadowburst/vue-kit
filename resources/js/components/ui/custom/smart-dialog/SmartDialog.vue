<script setup lang="ts">
import { Dialog } from '@/components/ui/dialog';
import { Drawer } from '@/components/ui/drawer';
import { useTailwindBreakpoints } from '@/composables';
import type { DialogRootEmits, DialogRootProps } from 'reka-ui';
import { useForwardPropsEmits } from 'reka-ui';
import type { DrawerRootProps } from 'vaul-vue';

type Props = DialogRootProps & DrawerRootProps;
const props = defineProps<Props>();

const emits = defineEmits<DialogRootEmits>();

const forwardedDialogProps = useForwardPropsEmits(props as DialogRootProps, emits);
const forwardedDrawerProps = useForwardPropsEmits(props as DrawerRootProps, emits);

const isOpen = defineModel<boolean>('open', {
    default: false,
});

const { md } = useTailwindBreakpoints();
</script>

<template>
    <Dialog v-if="md" v-bind="forwardedDialogProps" v-model:open="isOpen">
        <slot />
    </Dialog>
    <Drawer v-else v-bind="forwardedDrawerProps" v-model:open="isOpen">
        <slot />
    </Drawer>
</template>
