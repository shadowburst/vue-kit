import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { resolveDataTableBulkActions, resolveDataTableRowActions } from '../actions';
import type { DataTableBulkActions, DataTableRowActions } from '../interface';

type User = {
    id: number;
    name: string;
    active: boolean;
};

const TestIcon = defineComponent({
    render: () => h('svg'),
});

describe('DataTable action resolution', () => {
    it('resolves row action values and filters hidden actions', () => {
        const user = { id: 1, name: 'Ada', active: true };
        const deactivate = vi.fn();

        const actions: DataTableRowActions<User> = {
            view: {
                label: (row) => `view ${row.name}`,
                icon: TestIcon,
                href: (row) => `/users/${row.id}`,
                disabled: (row) => !row.active,
                variant: 'outline',
            },
            deactivate: {
                label: 'deactivate',
                icon: TestIcon,
                callback: (row) => deactivate(row),
                class: (row) => (row.active ? 'text-foreground' : 'text-muted-foreground'),
            },
            hidden: {
                label: 'hidden',
                icon: TestIcon,
                callback: vi.fn(),
                hidden: true,
            },
        };

        const resolvedActions = resolveDataTableRowActions(actions, user);

        expect(resolvedActions).toHaveLength(2);
        expect(resolvedActions[0]).toMatchObject({
            label: 'view Ada',
            href: '/users/1',
            disabled: false,
            variant: 'outline',
        });
        expect(resolvedActions[1]).toMatchObject({
            label: 'deactivate',
            class: 'text-foreground',
        });

        if (!resolvedActions[1] || !('callback' in resolvedActions[1])) {
            throw new Error('Expected a callback action.');
        }

        resolvedActions[1].callback?.();

        expect(deactivate).toHaveBeenCalledWith(user);
    });

    it('resolves bulk action values against selected rows', () => {
        const users = [
            { id: 1, name: 'Ada', active: true },
            { id: 2, name: 'Grace', active: false },
        ];
        const archive = vi.fn();

        const actions: DataTableBulkActions<User> = {
            archive: {
                label: (rows) => `archive ${rows.length}`,
                icon: TestIcon,
                callback: (rows) => archive(rows),
                disabled: (rows) => rows.some((row) => !row.active),
            },
            hidden: {
                label: 'hidden',
                icon: TestIcon,
                callback: vi.fn(),
                hidden: (rows) => rows.length > 1,
            },
        };

        const resolvedActions = resolveDataTableBulkActions(actions, users);

        expect(resolvedActions).toHaveLength(1);
        expect(resolvedActions[0]).toMatchObject({
            label: 'archive 2',
            disabled: true,
        });

        if (!resolvedActions[0] || !('callback' in resolvedActions[0])) {
            throw new Error('Expected a callback action.');
        }

        resolvedActions[0].callback?.();

        expect(archive).toHaveBeenCalledWith(users);
    });
});
