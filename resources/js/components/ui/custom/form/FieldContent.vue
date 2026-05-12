<script setup lang="ts">
import { FieldContent as ShadcnFieldContent } from '@/components/ui/field';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <ShadcnFieldContent :class="props.class">
        <slot />
    </ShadcnFieldContent>
</template>
