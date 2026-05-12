<script setup lang="ts">
import { FieldGroup as ShadcnFieldGroup } from '@/components/ui/field';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <ShadcnFieldGroup :class="props.class">
        <slot />
    </ShadcnFieldGroup>
</template>
