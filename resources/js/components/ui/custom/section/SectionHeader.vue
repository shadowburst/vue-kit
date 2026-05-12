<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div
        data-slot="section-header"
        :class="
            cn(
                'group/section-header @container/section-header grid auto-rows-min items-start gap-2 px-4 group-data-[size=sm]/section:gap-1 group-data-[size=sm]/section:px-3 has-data-[slot=section-action]:grid-cols-[1fr_auto] has-data-[slot=section-description]:grid-rows-[auto_auto] sm:px-8 sm:group-data-[size=sm]/section:px-6 [.border-b]:pb-8 group-data-[size=sm]/section:[.border-b]:pb-6',
                props.class,
            )
        "
    >
        <slot />
    </div>
</template>
