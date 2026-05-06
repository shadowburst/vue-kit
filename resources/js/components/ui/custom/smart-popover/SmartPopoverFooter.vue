<script setup lang="ts">
import { DrawerFooter } from '@/components/ui/drawer';
import { cn } from '@/lib/utils';
import { HTMLAttributes } from 'vue';
import { injectSmartPopoverContext } from './SmartPopover.vue';

type Props = {
    class?: HTMLAttributes['class'];
};
const props = defineProps<Props>();

const { isDesktop } = injectSmartPopoverContext();
</script>

<template>
    <div v-if="isDesktop" data-slot="popover-footer" :class="cn('flex flex-col-reverse gap-2 pt-3', props.class)">
        <slot />
    </div>
    <DrawerFooter v-else :class="cn('p-0', props.class)">
        <slot />
    </DrawerFooter>
</template>
