<script setup lang="ts" generic="TData">
import { Checkbox } from '@/components/ui/checkbox';
import { computed } from 'vue';
import { injectDataTableRowContext } from './DataTableRow.vue';

const { row } = injectDataTableRowContext<TData>();

const checked = computed<boolean>({
    get: () => row.value?.getIsSelected() ?? false,
    set: (value) => row.value?.toggleSelected(value),
});
</script>

<template>
    <Checkbox v-model="checked" aria-label="Select row" />
</template>
