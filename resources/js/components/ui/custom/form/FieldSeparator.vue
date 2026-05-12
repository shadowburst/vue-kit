<script setup lang="ts">
import { FieldSeparator as ShadcnFieldSeparator } from '@/components/ui/field';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <ShadcnFieldSeparator :class="props.class">
        <slot />
    </ShadcnFieldSeparator>
</template>
