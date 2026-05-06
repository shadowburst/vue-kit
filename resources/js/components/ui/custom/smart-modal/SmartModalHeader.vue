<script setup lang="ts">
import { DialogHeader } from '@/components/ui/dialog';
import { DrawerHeader } from '@/components/ui/drawer';
import { cn } from '@/lib/utils';
import { HTMLAttributes } from 'vue';
import { injectSmartModalRootContext } from './SmartModal.vue';

type Props = {
    class?: HTMLAttributes['class'];
};
const props = defineProps<Props>();

const { isDesktop } = injectSmartModalRootContext();
</script>

<template>
    <DialogHeader v-if="isDesktop" :class="props.class">
        <slot />
    </DialogHeader>
    <DrawerHeader v-else :class="cn('p-0', props.class)">
        <slot />
    </DrawerHeader>
</template>
