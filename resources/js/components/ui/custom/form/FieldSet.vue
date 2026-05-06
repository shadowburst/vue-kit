<script setup lang="ts">
import { FieldSet as ShadcnFieldSet } from '@/components/ui/field';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <ShadcnFieldSet :class="props.class">
        <slot />
    </ShadcnFieldSet>
</template>
