<script lang="ts">
import type { InertiaLinkVariants } from '@/components/ui/custom/inertia-link';
import type { HTMLAttributes } from 'vue';

export type InertiaModalLinkProps = {
    href: string;
    method?: string;
    data?: object;
    headers?: object;
    queryStringArrayFormat?: string;
    navigate?: boolean;
    prefetch?: boolean | string | (boolean | string)[];
    cacheFor?: number;
    as?: string;
    disabled?: boolean;
    variant?: InertiaLinkVariants['variant'];
    class?: HTMLAttributes['class'];
};
</script>

<script setup lang="ts">
import { inertiaLinkVariants } from '@/components/ui/custom/inertia-link';
import { cn } from '@/lib/utils';
import { ModalLink } from '@inertiaui/modal-vue';
import { reactiveOmit } from '@vueuse/core';
import { computed } from 'vue';

const props = withDefaults(defineProps<InertiaModalLinkProps>(), {
    prefetch: true,
});

const delegatedProps = reactiveOmit(props, 'as', 'disabled', 'variant', 'class', 'href');

const href = computed<string>(() => (props.disabled || !props.href ? '#' : props.href));
const as = computed<string>(() => props.as ?? 'a');
</script>

<template>
    <button v-if="disabled" disabled type="button" :class="cn(inertiaLinkVariants({ variant }), props.class)">
        <slot />
    </button>
    <ModalLink
        v-else
        v-bind="delegatedProps"
        :as="as"
        :href="href"
        :class="cn(inertiaLinkVariants({ variant }), props.class)"
    >
        <slot />
    </ModalLink>
</template>
