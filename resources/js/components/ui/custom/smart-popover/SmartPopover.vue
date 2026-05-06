<script lang="ts">
type SmartPopoverContext = {
    isDesktop: Ref<boolean>;
};

export const [injectSmartPopoverContext, provideSmartPopoverContext] =
    createContext<SmartPopoverContext>('SmartPopoverContext');
</script>

<script setup lang="ts">
import { Drawer } from '@/components/ui/drawer';
import { Popover } from '@/components/ui/popover';
import { useTailwindBreakpoints } from '@/composables';
import { createContext, DialogRootEmits, PopoverRootEmits, PopoverRootProps, useForwardPropsEmits } from 'reka-ui';
import { DrawerRootProps } from 'vaul-vue';
import { Ref } from 'vue';

type Props = PopoverRootProps & DrawerRootProps;
const props = defineProps<Props>();

type Emits = PopoverRootEmits & DialogRootEmits;
const emits = defineEmits<Emits>();

const forwardedPopoverProps = useForwardPropsEmits(props as PopoverRootProps, emits);
const forwardedDrawerProps = useForwardPropsEmits(props as DrawerRootProps, emits);

const isOpen = defineModel<boolean>('open', {
    default: false,
});

const { sm: isDesktop } = useTailwindBreakpoints();

provideSmartPopoverContext({
    isDesktop,
});
</script>

<template>
    <Popover v-if="isDesktop" v-bind="forwardedPopoverProps" v-model:open="isOpen">
        <slot />
    </Popover>
    <Drawer v-else v-bind="forwardedDrawerProps" v-model:open="isOpen">
        <slot />
    </Drawer>
</template>
