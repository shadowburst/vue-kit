<script setup lang="ts">
import { DialogHeader } from '@/components/ui/dialog';
import { DrawerHeader } from '@/components/ui/drawer';
import { useTailwindBreakpoints } from '@/composables';
import type { HTMLAttributes } from 'vue';

type Props = {
    class?: HTMLAttributes['class'];
};
const props = defineProps<Props>();

const { md } = useTailwindBreakpoints();
</script>

<template>
    <DialogHeader v-if="md" :class="props.class">
        <slot />
    </DialogHeader>
    <DrawerHeader v-else :class="props.class">
        <slot />
    </DrawerHeader>
</template>
