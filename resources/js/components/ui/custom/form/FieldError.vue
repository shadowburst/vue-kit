<script setup lang="ts">
import { FieldError as ShadcnFieldError } from '@/components/ui/field';
import type { HTMLAttributes } from 'vue';
import { computed, onBeforeUnmount, useId, useSlots, watch } from 'vue';
import { injectFieldContext } from './context';

type ErrorEntry = string | { message: string | undefined } | undefined;

const props = defineProps<{
    id?: string;
    class?: HTMLAttributes['class'];
    errors?: ErrorEntry[];
}>();

const ctx = injectFieldContext(null);
const slots = useSlots();
const fallbackId = useId();
const resolvedId = computed(() => props.id ?? fallbackId);

const hasErrorContent = computed(() => {
    if (slots.default) {
        return true;
    }

    if (!props.errors) {
        return false;
    }

    return props.errors.some((entry) => {
        if (entry === undefined) {
            return false;
        }

        const message = typeof entry === 'string' ? entry : entry.message;

        return Boolean(message);
    });
});

if (ctx) {
    watch(
        [hasErrorContent, resolvedId] as const,
        ([visible, id]) => {
            ctx.setErrorId(visible ? id : undefined);
        },
        { immediate: true },
    );

    onBeforeUnmount(() => {
        ctx.setErrorId(undefined);
    });
}
</script>

<template>
    <ShadcnFieldError v-if="hasErrorContent" :id="resolvedId" :class="props.class" :errors="errors">
        <slot />
    </ShadcnFieldError>
</template>
