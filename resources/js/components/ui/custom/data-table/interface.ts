import type { ActionItem } from '@/components/ui/custom/action-menu';
import type { MaybeRefOrGetter } from 'vue';

type DataTableActionValue<TContext, TValue> = TValue | ((context: TContext) => TValue);

type DataTableBaseAction<TContext> = {
    label: DataTableActionValue<TContext, string>;
    icon: DataTableActionValue<TContext, ActionItem['icon']>;
    disabled?: DataTableActionValue<TContext, MaybeRefOrGetter<boolean>>;
    hidden?: DataTableActionValue<TContext, MaybeRefOrGetter<boolean>>;
    variant?: DataTableActionValue<TContext, ActionItem['variant']>;
    size?: DataTableActionValue<TContext, ActionItem['size']>;
    class?: DataTableActionValue<TContext, ActionItem['class']>;
};

export type DataTableHrefAction<TContext> = DataTableBaseAction<TContext> & {
    href: DataTableActionValue<TContext, string>;
    callback?: never;
};

export type DataTableCallbackAction<TContext> = DataTableBaseAction<TContext> & {
    callback: (context: TContext) => void;
    href?: never;
};

export type DataTableRowAction<TData> = DataTableCallbackAction<TData> | DataTableHrefAction<TData>;
export type DataTableBulkAction<TData> = DataTableCallbackAction<TData[]> | DataTableHrefAction<TData[]>;
export type DataTableRowActions<TData> = Record<string, DataTableRowAction<TData>>;
export type DataTableBulkActions<TData> = Record<string, DataTableBulkAction<TData>>;
export type DataTableResponsiveColumnVisibility = Record<string, MaybeRefOrGetter<boolean>>;

export type { Table as DataTableState } from '@tanstack/vue-table';
