<script setup lang="ts">
import { DialogFooter } from '@/components/ui/dialog';
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
    <DialogFooter v-if="md" :class="props.class">
        <slot />
    </DialogFooter>
    <DrawerFooter v-else :class="props.class">
        <slot />
    </DrawerFooter>
</template>
