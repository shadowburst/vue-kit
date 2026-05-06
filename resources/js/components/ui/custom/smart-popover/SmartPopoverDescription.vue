<script setup lang="ts">
import { DrawerDescription } from '@/components/ui/drawer';
import { useTailwindBreakpoints } from '@/composables';
import { reactiveOmit } from '@vueuse/core';
import { useForwardProps } from 'reka-ui';
import type { DrawerDescriptionProps } from 'vaul-vue';
import type { HTMLAttributes } from 'vue';

type Props = DrawerDescriptionProps & {
    class?: HTMLAttributes['class'];
};
const props = defineProps<Props>();

const forwardedDrawerProps = useForwardProps(reactiveOmit(props, 'class') as DrawerDescriptionProps);

const { md } = useTailwindBreakpoints();
</script>

<template>
    <p v-if="md" data-slot="popover-description" :class="props.class">
        <slot />
    </p>
    <DrawerDescription v-else v-bind="forwardedDrawerProps" :class="props.class">
        <slot />
    </DrawerDescription>
</template>
