import type { ActionItem } from '@/components/ui/custom/action-menu';
import { toValue } from 'vue';
import type {
    DataTableBulkActions,
    DataTableCallbackAction,
    DataTableHrefAction,
    DataTableRowActions,
} from './interface';

function resolveActionValue<TContext, TValue>(
    value: TValue | ((context: TContext) => TValue),
    context: TContext,
): TValue {
    return typeof value === 'function' ? (value as (context: TContext) => TValue)(context) : value;
}

function resolveDataTableAction<TContext>(
    action: DataTableCallbackAction<TContext> | DataTableHrefAction<TContext>,
    context: TContext,
): ActionItem | undefined {
    const hidden = action.hidden === undefined ? false : toValue(resolveActionValue(action.hidden, context));

    if (hidden) {
        return;
    }

    const baseAction = {
        label: resolveActionValue(action.label, context),
        icon: resolveActionValue(action.icon, context),
        disabled: action.disabled === undefined ? undefined : resolveActionValue(action.disabled, context),
        variant: action.variant === undefined ? undefined : resolveActionValue(action.variant, context),
        size: action.size === undefined ? undefined : resolveActionValue(action.size, context),
        class: action.class === undefined ? undefined : resolveActionValue(action.class, context),
    };

    if (action.href !== undefined) {
        return {
            ...baseAction,
            href: resolveActionValue(action.href, context),
        };
    }

    return {
        ...baseAction,
        callback: () => action.callback(context),
    };
}

export function resolveDataTableRowActions<TData>(actions: DataTableRowActions<TData>, row: TData): ActionItem[] {
    return Object.values(actions).flatMap((action) => {
        const resolvedAction = resolveDataTableAction(action, row);

        return resolvedAction ? [resolvedAction] : [];
    });
}

export function resolveDataTableBulkActions<TData>(actions: DataTableBulkActions<TData>, rows: TData[]): ActionItem[] {
    return Object.values(actions).flatMap((action) => {
        const resolvedAction = resolveDataTableAction(action, rows);

        return resolvedAction ? [resolvedAction] : [];
    });
}
