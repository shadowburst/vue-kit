<script lang="ts">
export type LinkProps = InertiaLinkProps & {
    disabled?: boolean;
    variant?: InertiaLinkVariants['variant'];
    class?: HTMLAttributes['class'];
};
</script>

<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { reactiveOmit } from '@vueuse/core';
import { useForwardProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import type { InertiaLinkVariants } from '.';
import { inertiaLinkVariants } from '.';

const props = withDefaults(defineProps<LinkProps>(), {
    prefetch: true,
});
const delegatedProps = reactiveOmit(props, 'as', 'disabled', 'href', 'variant', 'class');
const forwarded = useForwardProps(delegatedProps);

const disabled = computed((): LinkProps['disabled'] => props.disabled || undefined);
const as = computed((): LinkProps['as'] => (props.disabled ? 'button' : props.as));
const method = computed((): LinkProps['method'] => {
    if (props.method) {
        return props.method;
    }

    if (typeof props.href === 'string') {
        return 'get';
    }

    return props.href?.method ?? 'get';
});
const href = computed((): LinkProps['href'] => (props.disabled || !props.href ? '#' : props.href));
const prefetch = computed((): LinkProps['prefetch'] => props.prefetch && method.value === 'get');
</script>

<template>
    <Link v-bind="forwarded" :disabled :as :href :prefetch :class="cn(inertiaLinkVariants({ variant }), props.class)">
        <slot />
    </Link>
</template>
