import type { UrlMethodPair } from '@inertiajs/core';
import { router } from '@inertiajs/core';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { effectScope, nextTick } from 'vue';
import { useDataTableFilters } from '../useDataTableFilters';
import { useFilters } from '../useFilters';

const visit = vi.hoisted(() => vi.fn());

vi.mock('@inertiajs/core', async (importOriginal) => {
    const actual = await importOriginal();

    return {
        ...(actual as object),
        router: {
            visit,
        },
    };
});

type FilterForm = {
    q?: string;
    page?: number;
    per_page?: number;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
    status?: string;
    tags?: string[];
    archived?: boolean;
};

const usersIndex = { method: 'get', url: '/users' } satisfies UrlMethodPair;

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

beforeEach(() => {
    visit.mockReset();
    sessionStorage.clear();
    window.history.replaceState({}, '', '/users');
});

describe('useFilters', () => {
    it('submits non-empty values and omits empty query values', () => {
        const { result: filters, stop } = scoped(() =>
            useFilters(usersIndex, {
                q: undefined,
                status: undefined,
                tags: undefined,
                archived: undefined,
            } as FilterForm),
        );

        try {
            filters.q = '   ';
            filters.status = 'active';
            filters.tags = [];
            filters.archived = false;

            filters.submit({ only: ['users'] });

            expect(router.visit).toHaveBeenCalledWith(
                usersIndex,
                expect.objectContaining({
                    data: {
                        status: 'active',
                        archived: false,
                    },
                    preserveScroll: true,
                    preserveState: true,
                    replace: true,
                    only: ['users'],
                }),
            );
        } finally {
            stop();
        }
    });

    it('restores remembered values for the URL path', () => {
        sessionStorage.setItem('/users', JSON.stringify({ q: 'ada', status: 'active' }));

        const { result: filters, stop } = scoped(() =>
            useFilters(usersIndex, {
                q: undefined,
                status: undefined,
            } as FilterForm),
        );

        try {
            expect(filters.q).toBe('ada');
            expect(filters.status).toBe('active');
        } finally {
            stop();
        }
    });

    it('clears remembered values when reset_filters is present', () => {
        sessionStorage.setItem('/users', JSON.stringify({ q: 'ada', status: 'active' }));
        window.history.replaceState({}, '', '/users?reset_filters=1');

        const { result: filters, stop } = scoped(() =>
            useFilters(usersIndex, {
                q: undefined,
                status: undefined,
            } as FilterForm),
        );

        try {
            expect(filters.q).toBeUndefined();
            expect(filters.status).toBeUndefined();
        } finally {
            stop();
        }
    });

    it('remembers raw form values instead of transformed submission values', async () => {
        const { result: filters, stop } = scoped(() =>
            useFilters(usersIndex, {
                status: undefined,
            } as FilterForm),
        );

        try {
            filters.transform((data) => ({ ...data, status: data.status?.toUpperCase() }));
            filters.status = 'active';

            await nextTick();

            expect(JSON.parse(sessionStorage.getItem('/users') ?? '{}')).toEqual({ status: 'active' });
        } finally {
            stop();
        }
    });

    it('returns the public form object from transform chains', () => {
        const { result: filters, stop } = scoped(() =>
            useFilters(usersIndex, {
                status: undefined,
            } as FilterForm),
        );

        try {
            expect(filters.transform((data) => data)).toBe(filters);
        } finally {
            stop();
        }
    });

    it('updates form lifecycle state when submitting', () => {
        const { result: filters, stop } = scoped(() =>
            useFilters(usersIndex, {
                status: undefined,
            } as FilterForm),
        );

        try {
            filters.submit();

            const visitOptions = visit.mock.calls[0][1];

            visitOptions.onStart({});
            expect(filters.processing).toBe(true);

            visitOptions.onError({ status: 'Invalid status' });
            expect(filters.errors.status).toBe('Invalid status');
            expect(filters.hasErrors).toBe(true);

            visitOptions.onSuccess({});
            expect(filters.errors).toEqual({});
            expect(filters.hasErrors).toBe(false);
            expect(filters.wasSuccessful).toBe(true);
            expect(filters.recentlySuccessful).toBe(true);

            visitOptions.onFinish({});
            expect(filters.processing).toBe(false);
            expect(filters.progress).toBeNull();
        } finally {
            stop();
        }
    });
});

describe('useDataTableFilters', () => {
    it('does not submit on initialization', () => {
        const { stop } = scoped(() =>
            useDataTableFilters(usersIndex, {
                q: undefined,
                page: undefined,
                status: undefined,
            } as FilterForm),
        );

        try {
            expect(router.visit).not.toHaveBeenCalled();
        } finally {
            stop();
        }
    });

    it('ignores table chrome fields when detecting active custom filters', () => {
        const { result: filters, stop } = scoped(() =>
            useDataTableFilters(usersIndex, {
                q: undefined,
                page: undefined,
                per_page: undefined,
                sort_by: undefined,
                sort_direction: undefined,
                status: undefined,
            } as FilterForm),
        );

        try {
            filters.q = 'ada';
            filters.page = 2;
            filters.per_page = 25;
            filters.sort_by = 'name';
            filters.sort_direction = 'asc';

            expect(filters.hasActiveFilters).toBe(false);

            filters.status = 'active';

            expect(filters.hasActiveFilters).toBe(true);
        } finally {
            stop();
        }
    });

    it('treats empty custom filter values as inactive', () => {
        const { result: filters, stop } = scoped(() =>
            useDataTableFilters(usersIndex, {
                status: undefined,
                tags: undefined,
            } as FilterForm),
        );

        try {
            filters.status = '   ';
            filters.tags = [];

            expect(filters.hasActiveFilters).toBe(false);
        } finally {
            stop();
        }
    });

    it('resets custom filters while preserving table chrome fields', () => {
        const { result: filters, stop } = scoped(() =>
            useDataTableFilters(usersIndex, {
                q: undefined,
                page: undefined,
                per_page: undefined,
                sort_by: undefined,
                sort_direction: undefined,
                status: undefined,
                tags: undefined,
            } as FilterForm),
        );

        try {
            filters.q = 'ada';
            filters.page = 2;
            filters.per_page = 25;
            filters.sort_by = 'name';
            filters.sort_direction = 'asc';
            filters.status = 'active';
            filters.tags = ['admin'];

            expect(filters.resetFilters()).toBe(filters);

            expect(filters.q).toBe('ada');
            expect(filters.page).toBe(2);
            expect(filters.per_page).toBe(25);
            expect(filters.sort_by).toBe('name');
            expect(filters.sort_direction).toBe('asc');
            expect(filters.status).toBeUndefined();
            expect(filters.tags).toBeUndefined();
        } finally {
            stop();
        }
    });

    it('keeps native Inertia dirty and reset semantics separate from active filters', async () => {
        const { result: filters, stop } = scoped(() =>
            useDataTableFilters(usersIndex, {
                q: undefined,
                status: undefined,
            } as FilterForm),
        );

        try {
            filters.q = 'ada';
            await nextTick();

            expect(filters.isDirty).toBe(true);
            expect(filters.hasActiveFilters).toBe(false);

            filters.reset();

            expect(filters.q).toBeUndefined();
        } finally {
            stop();
        }
    });
});
