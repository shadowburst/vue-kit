<script setup lang="ts">
import { DrawerHeader } from '@/components/ui/drawer';
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
    <div v-if="isDesktop" data-slot="popover-header" :class="cn('flex flex-col gap-1.5 pb-3', props.class)">
        <slot />
    </div>
    <DrawerHeader v-else :class="cn('p-0', props.class)">
        <slot />
    </DrawerHeader>
</template>
