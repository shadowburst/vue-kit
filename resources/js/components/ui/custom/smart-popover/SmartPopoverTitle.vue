<script setup lang="ts">
import { DrawerTitle } from '@/components/ui/drawer';
import { useTailwindBreakpoints } from '@/composables';
import { reactiveOmit } from '@vueuse/core';
import { useForwardProps } from 'reka-ui';
import type { DrawerTitleProps } from 'vaul-vue';
import type { HTMLAttributes } from 'vue';

type Props = DrawerTitleProps & {
    class?: HTMLAttributes['class'];
};
const props = defineProps<Props>();

const forwardedDrawerProps = useForwardProps(reactiveOmit(props, 'class') as DrawerTitleProps);

const { md } = useTailwindBreakpoints();
</script>

<template>
    <div v-if="md" data-slot="popover-title" :class="props.class">
        <slot />
    </div>
    <DrawerTitle v-else v-bind="forwardedDrawerProps" :class="props.class">
        <slot />
    </DrawerTitle>
</template>
