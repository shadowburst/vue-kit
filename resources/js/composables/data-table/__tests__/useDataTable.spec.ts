import type { ColumnDef } from '@tanstack/vue-table';
import { describe, expect, it } from 'vitest';
import { effectScope, nextTick, ref } from 'vue';
import { useDataTable } from '../useDataTable';

type User = {
    id: number;
    name: string;
    email: string;
    role: string;
};

const users: User[] = [
    { id: 1, name: 'Ada', email: 'ada@example.com', role: 'admin' },
    { id: 2, name: 'Grace', email: 'grace@example.com', role: 'member' },
];

const columns: ColumnDef<User>[] = [{ accessorKey: 'name' }, { accessorKey: 'email' }, { accessorKey: 'role' }];

function scoped<T>(callback: () => T): { result: T; stop: () => void } {
    const scope = effectScope();
    const result = scope.run(callback);

    if (result === undefined) {
        throw new Error('Expected effect scope to return a result.');
    }

    return {
        result,
        stop: () => scope.stop(),
    };
}

describe('useDataTable', () => {
    it('maps responsive column visibility refs into TanStack column visibility state', async () => {
        const md = ref(false);
        const {
            result: { table },
            stop,
        } = scoped(() =>
            useDataTable({
                data: users,
                columns,
                responsiveColumnVisibility: { email: md },
            }),
        );

        try {
            expect(table.getState().columnVisibility).toEqual({ email: false });

            md.value = true;
            await nextTick();

            expect(table.getState().columnVisibility).toEqual({ email: true });
        } finally {
            stop();
        }
    });

    it('preserves initial visibility for unconfigured columns', async () => {
        const md = ref(false);
        const {
            result: { table },
            stop,
        } = scoped(() =>
            useDataTable({
                data: users,
                columns,
                initialState: {
                    columnVisibility: {
                        name: false,
                        role: false,
                    },
                },
                responsiveColumnVisibility: { email: md },
            }),
        );

        try {
            expect(table.getState().columnVisibility).toEqual({ name: false, role: false, email: false });

            md.value = true;
            await nextTick();

            expect(table.getState().columnVisibility).toEqual({ name: false, role: false, email: true });
        } finally {
            stop();
        }
    });
});
