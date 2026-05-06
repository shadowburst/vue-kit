<script setup lang="ts">
import { FieldDescription as ShadcnFieldDescription } from '@/components/ui/field';
import type { HTMLAttributes } from 'vue';
import { computed, onBeforeUnmount, useId, useSlots, watch } from 'vue';
import { injectFieldContext } from './context';

const props = defineProps<{
    id?: string;
    class?: HTMLAttributes['class'];
}>();

const ctx = injectFieldContext(null);
const slots = useSlots();
const fallbackId = useId();
const resolvedId = computed(() => props.id ?? fallbackId);
const hasContent = computed(() => Boolean(slots.default));

if (ctx) {
    watch(
        [hasContent, resolvedId] as const,
        ([visible, id]) => {
            ctx.setDescriptionId(visible ? id : undefined);
        },
        { immediate: true },
    );

    onBeforeUnmount(() => {
        ctx.setDescriptionId(undefined);
    });
}
</script>

<template>
    <ShadcnFieldDescription v-if="hasContent" :id="resolvedId" :class="props.class">
        <slot />
    </ShadcnFieldDescription>
</template>
