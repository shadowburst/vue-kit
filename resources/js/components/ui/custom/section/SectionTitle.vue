<script setup lang="ts">
import { cn } from '@/lib/utils';
import { reactiveOmit } from '@vueuse/core';
import type { PrimitiveProps } from 'reka-ui';
import { Primitive, useForwardProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { injectSectionContext } from './context';

type Props = PrimitiveProps & {
    class?: HTMLAttributes['class'];
};

const props = defineProps<Props>();

const sectionContext = injectSectionContext({
    size: computed(() => 'default' as const),
});

const resolvedAs = computed(() => props.as ?? (sectionContext.size.value === 'sm' ? 'h3' : 'h2'));

const delegatedProps = reactiveOmit(props, 'class', 'as');
const forwarded = useForwardProps(delegatedProps);
</script>

<template>
    <Primitive
        v-bind="forwarded"
        data-slot="section-title"
        :as="resolvedAs"
        :class="
            cn('cn-font-heading text-2xl leading-snug font-semibold group-data-[size=sm]/section:text-xl', props.class)
        "
    >
        <slot />
    </Primitive>
</template>
