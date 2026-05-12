<script setup lang="ts">
import { FieldTitle as ShadcnFieldTitle } from '@/components/ui/field';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <ShadcnFieldTitle :class="props.class">
        <slot />
    </ShadcnFieldTitle>
</template>
