<script lang="ts" setup>
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <ol
        data-slot="breadcrumb-list"
        :class="cn('flex flex-wrap items-center gap-1.5 text-sm wrap-break-word text-muted-foreground', props.class)"
    >
        <slot />
    </ol>
</template>
