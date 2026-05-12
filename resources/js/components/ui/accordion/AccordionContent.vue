<script setup lang="ts">
import { cn } from '@/lib/utils';
import { reactiveOmit } from '@vueuse/core';
import type { AccordionContentProps } from 'reka-ui';
import { AccordionContent } from 'reka-ui';
import type { HTMLAttributes } from 'vue';

const props = defineProps<AccordionContentProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');
</script>

<template>
    <AccordionContent
        data-slot="accordion-content"
        v-bind="delegatedProps"
        class="overflow-hidden text-sm data-open:animate-accordion-down data-closed:animate-accordion-up"
    >
        <div
            :class="
                cn(
                    'pt-0 pb-2.5 [&_a]:underline [&_a]:underline-offset-3 [&_a]:hover:text-foreground [&_p:not(:last-child)]:mb-4',
                    props.class,
                )
            "
        >
            <slot />
        </div>
    </AccordionContent>
</template>
