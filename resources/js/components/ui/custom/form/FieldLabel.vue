<script setup lang="ts">
import { FieldLabel as ShadcnFieldLabel } from '@/components/ui/field';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { injectFieldContext } from './context';

const props = withDefaults(
    defineProps<{
        for?: string;
        required?: boolean;
        class?: HTMLAttributes['class'];
    }>(),
    {
        for: undefined,
        required: undefined,
        class: undefined,
    },
);

const ctx = injectFieldContext(null);

const resolvedFor = computed(() => props.for ?? ctx?.id);
const resolvedRequired = computed(() => props.required ?? ctx?.required.value ?? false);
</script>

<template>
    <ShadcnFieldLabel :for="resolvedFor" :class="props.class">
        <slot />

        <span v-if="resolvedRequired" aria-hidden="true">*</span>
    </ShadcnFieldLabel>
</template>
