<script setup lang="ts" generic="TData">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { ActionMenu } from '@/components/ui/custom/action-menu';
import { ChevronDownIcon } from '@lucide/vue';
import type { CheckboxCheckedState } from 'reka-ui';
import { computed } from 'vue';
import { resolveDataTableBulkActions } from './actions';
import { injectDataTableRootContext } from './DataTable.vue';

const { table, bulkActions } = injectDataTableRootContext<TData>();

const checked = computed<CheckboxCheckedState>({
    get: () => table.value.getIsAllPageRowsSelected() || (table.value.getIsSomeRowsSelected() && 'indeterminate'),
    set: (value) => table.value.toggleAllPageRowsSelected(value === true),
});

const selectedRows = computed(() => table.value.getSelectedRowModel().rows.map((row) => row.original));
const actions = computed(() => resolveDataTableBulkActions(bulkActions.value, selectedRows.value));
</script>

<template>
    <ActionMenu v-if="selectedRows.length > 0 && actions.length" :actions>
        <Button variant="ghost" size="sm" class="px-2">
            <Checkbox v-model="checked" class="my-auto" @click.stop.prevent />
            <ChevronDownIcon />
        </Button>
    </ActionMenu>
    <Button v-else variant="ghost" size="sm" class="px-2">
        <Checkbox v-model="checked" class="my-auto" @click.stop.prevent />
    </Button>
</template>
