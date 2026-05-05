<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <Label
        data-slot="field-label"
        :class="
            cn(
                'group/field-label peer/field-label flex w-fit gap-2 leading-snug group-data-[disabled=true]/field:opacity-50 has-data-checked:border-primary/30 has-data-checked:bg-primary/5 has-[>[data-slot=field]]:rounded-lg has-[>[data-slot=field]]:border *:data-[slot=field]:p-2.5 dark:has-data-checked:border-primary/20 dark:has-data-checked:bg-primary/10',
                'has-[>[data-slot=field]]:w-full has-[>[data-slot=field]]:flex-col',
                props.class,
            )
        "
    >
        <slot />
    </Label>
</template>
