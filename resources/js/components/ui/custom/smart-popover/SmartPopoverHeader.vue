<script setup lang="ts">
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
    <div v-if="md" data-slot="popover-header" :class="props.class">
        <slot />
    </div>
    <DrawerHeader v-else :class="props.class">
        <slot />
    </DrawerHeader>
</template>
