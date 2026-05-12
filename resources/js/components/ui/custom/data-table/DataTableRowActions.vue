<script setup lang="ts" generic="TData">
import { Button } from '@/components/ui/button';
import { ActionMenu } from '@/components/ui/custom/action-menu';
import { EllipsisVerticalIcon } from '@lucide/vue';
import { computed } from 'vue';
import { resolveDataTableRowActions } from './actions';
import { injectDataTableRootContext } from './DataTable.vue';
import { injectDataTableRowContext } from './DataTableRow.vue';

const { actions: rowActions } = injectDataTableRootContext<TData>();
const { row } = injectDataTableRowContext<TData>();

const actions = computed(() => (!row.value ? [] : resolveDataTableRowActions(rowActions.value, row.value.original)));
</script>

<template>
    <ActionMenu v-if="actions.length && row" :actions :buttons="2">
        <Button role="actions" variant="ghost" size="sm">
            <EllipsisVerticalIcon />
        </Button>
    </ActionMenu>
</template>
