<script setup lang="ts">
import { cn } from '@/lib/utils';
import { reactiveOmit } from '@vueuse/core';
import type { PrimitiveProps } from 'reka-ui';
import { Primitive, useForwardProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import type { SectionSize } from './context';
import { provideSectionContext } from './context';

type Props = PrimitiveProps & {
    class?: HTMLAttributes['class'];
    size?: SectionSize;
};

const props = withDefaults(defineProps<Props>(), {
    as: 'section',
    size: 'default',
});

provideSectionContext({
    size: computed(() => props.size),
});

const delegatedProps = reactiveOmit(props, 'class', 'size');
const forwarded = useForwardProps(delegatedProps);
</script>

<template>
    <Primitive
        v-bind="forwarded"
        data-slot="section"
        :data-size="size"
        :class="
            cn(
                'group/section flex flex-col gap-6 py-4 data-[size=sm]:gap-4 data-[size=sm]:py-3 sm:py-8 sm:data-[size=sm]:py-6',
                props.class,
            )
        "
    >
        <slot />
    </Primitive>
</template>
