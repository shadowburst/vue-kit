<script setup lang="ts">
import { cn } from '@/lib/utils';
import { reactiveOmit } from '@vueuse/core';
import type { PaginationListProps } from 'reka-ui';
import { PaginationList } from 'reka-ui';
import type { HTMLAttributes } from 'vue';

const props = defineProps<PaginationListProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');
</script>

<template>
    <PaginationList
        v-slot="slotProps"
        data-slot="pagination-content"
        v-bind="delegatedProps"
        :class="cn('flex items-center gap-0.5', props.class)"
    >
        <slot v-bind="slotProps" />
    </PaginationList>
</template>
