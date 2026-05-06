<script setup lang="ts">
import { FieldLegend as ShadcnFieldLegend } from '@/components/ui/field';
import type { HTMLAttributes } from 'vue';

const props = withDefaults(
    defineProps<{
        class?: HTMLAttributes['class'];
        variant?: 'legend' | 'label';
    }>(),
    {
        variant: 'legend',
    },
);
</script>

<template>
    <ShadcnFieldLegend :variant="variant" :class="props.class">
        <slot />
    </ShadcnFieldLegend>
</template>
