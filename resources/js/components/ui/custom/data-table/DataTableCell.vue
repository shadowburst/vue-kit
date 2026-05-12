<script setup lang="ts" generic="TData">
import { TableCell } from '@/components/ui/table';
import { cn } from '@/lib/utils';
import type { Cell } from '@tanstack/vue-table';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { injectDataTableRowContext } from './DataTableRow.vue';

type Props = {
    cell?: Cell<TData, unknown>;
    class?: HTMLAttributes['class'];
};
const props = defineProps<Props>();

const style = computed((): HTMLAttributes['style'] => {
    const column = props.cell?.column;

    if (!column) {
        return {};
    }

    const columnStyle: HTMLAttributes['style'] = {};
    const isPinned = column.getIsPinned();

    if (isPinned) {
        columnStyle.left = isPinned === 'left' ? `${column.getStart('left')}px` : undefined;
        columnStyle.right = isPinned === 'right' ? `${column.getAfter('right')}px` : undefined;
        columnStyle.position = 'sticky';
        columnStyle.width = column.getSize();
        columnStyle.zIndex = 1;

        if (isPinned === 'left' && column.getIsLastColumn('left')) {
            columnStyle.boxShadow = '-1px 0 0 0 var(--border) inset';
        }

        if (isPinned === 'right' && column.getIsFirstColumn('right')) {
            columnStyle.boxShadow = '1px 0 0 0 var(--border) inset';
        }
    }

    return columnStyle;
});

const { row } = injectDataTableRowContext();
</script>

<template>
    <TableCell
        :class="
            cn(
                '[&:has([role=actions])]:text-end',
                cell?.column.getIsPinned() ? 'bg-background group-hover/data-table-row:bg-muted' : '',
                row?.getIsSelected() ? 'bg-muted' : '',
                props.class,
            )
        "
        :style
    >
        <slot />
    </TableCell>
</template>
