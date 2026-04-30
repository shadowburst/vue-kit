<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div data-slot="card-description" :class="cn('text-sm text-muted-foreground', props.class)">
        <slot />
    </div>
</template>
