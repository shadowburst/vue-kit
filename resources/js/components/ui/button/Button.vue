<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Primitive } from 'reka-ui';
import type { ButtonProps } from '.';
import { buttonVariants } from '.';

const props = withDefaults(defineProps<ButtonProps>(), {
    as: 'button',
});
</script>

<template>
    <Primitive
        data-slot="button"
        :data-variant="variant"
        :data-size="size"
        :as="as"
        :as-child="asChild"
        :class="cn(buttonVariants({ variant, size }), props.class)"
    >
        <slot />
    </Primitive>
</template>
