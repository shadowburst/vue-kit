<script setup lang="ts">
import { DialogScrollContent } from '@/components/ui/dialog';
import { DrawerContent } from '@/components/ui/drawer';
import { useTailwindBreakpoints } from '@/composables';
import { reactiveOmit } from '@vueuse/core';
import type { DialogContentProps } from 'reka-ui';
import { useForwardProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';

type Props = DialogContentProps & {
    class?: HTMLAttributes['class'];
};
const props = defineProps<Props>();

const forwarded = useForwardProps(reactiveOmit(props, 'class'));

const { md } = useTailwindBreakpoints();
</script>

<template>
    <DialogScrollContent v-if="md" v-bind="forwarded" :class="props.class">
        <slot />
    </DialogScrollContent>
    <DrawerContent v-else v-bind="forwarded" :class="props.class">
        <slot />
    </DrawerContent>
</template>
