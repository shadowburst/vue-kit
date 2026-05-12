import type { FormDataKeys, FormDataType, UrlMethodPair, UseFormSubmitOptions } from '@inertiajs/core';
import type { InertiaForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { watch } from 'vue';
import { hasFilterValue, useFilters } from './useFilters';

type DataTableFilters = {
    q?: string;
    page?: number;
    per_page?: number;
    sort_by?: string;
    sort_direction?: 'asc' | 'desc';
};

const dataTableFields = ['q', 'page', 'per_page', 'sort_by', 'sort_direction'] satisfies (keyof DataTableFilters)[];

export type DataTableFiltersForm<TForm extends object> = InertiaForm<TForm> & {
    readonly hasActiveFilters: boolean;
    resetFilters(): DataTableFiltersForm<TForm>;
};

function isDataTableField(key: string): key is keyof DataTableFilters {
    return (dataTableFields as string[]).includes(key);
}

export function useDataTableFilters<
    TForm extends FormDataType<DataTableFilters> &
        FormDataType<{ [K in keyof Omit<TForm, keyof DataTableFilters>]: TForm[K] | undefined }>,
>(
    urlMethodPair: UrlMethodPair | (() => UrlMethodPair),
    data: TForm | (() => TForm),
    options: UseFormSubmitOptions = {},
): DataTableFiltersForm<TForm> {
    const filters = useFilters(urlMethodPair, data) as DataTableFiltersForm<TForm>;

    Object.defineProperties(filters, {
        hasActiveFilters: {
            get() {
                return Object.entries(filters.data()).some(
                    ([key, value]) => !isDataTableField(key) && hasFilterValue(value),
                );
            },
        },
        resetFilters: {
            value() {
                filters.reset(
                    ...(Object.keys(filters.data()).filter((key) => !isDataTableField(key)) as FormDataKeys<TForm>[]),
                );

                return filters;
            },
        },
    });

    const resetPage = useDebounceFn(() => {
        if (filters.page === 1) {
            filters.submit(options);
        } else {
            filters.page = 1;
        }
    }, 350);

    // Whenever a filter changes, reset to page 1 and reload with debounce.
    // If changing page, submit immediately.
    watch(
        () => filters.data(),
        (newData, oldData) => {
            if (newData.page === oldData?.page) {
                resetPage();

                return;
            }

            filters.submit(options);
        },
        { deep: true },
    );

    return filters;
}
