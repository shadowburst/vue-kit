<script setup lang="ts">
import { DrawerFooter } from '@/components/ui/drawer';
import { useTailwindBreakpoints } from '@/composables';
import type { HTMLAttributes } from 'vue';

type Props = {
    class?: HTMLAttributes['class'];
};
const props = defineProps<Props>();

const { md } = useTailwindBreakpoints();
</script>

<template>
    <div v-if="md" data-slot="popover-footer" :class="props.class">
        <slot />
    </div>
    <DrawerFooter v-else :class="props.class">
        <slot />
    </DrawerFooter>
</template>
