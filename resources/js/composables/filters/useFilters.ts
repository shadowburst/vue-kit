import type {
    FormDataConvertible,
    FormDataErrors,
    FormDataType,
    RequestPayload,
    UrlMethodPair,
    UseFormTransformCallback,
} from '@inertiajs/core';
import { router, UseFormUtils } from '@inertiajs/core';
import type { InertiaForm } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { syncRefs, useSessionStorage, useUrlSearchParams } from '@vueuse/core';
import { ref, toValue } from 'vue';

export function hasFilterValue(value: unknown): boolean {
    if (value === undefined || value === null) {
        return false;
    }

    if (Array.isArray(value) && value.length === 0) {
        return false;
    }

    if (typeof value === 'string' && value.trim() === '') {
        return false;
    }

    return true;
}

function filledQueryValues(data: object): RequestPayload {
    return Object.entries(data).reduce(
        (query, [key, value]) =>
            hasFilterValue(value) ? Object.assign(query, { [key]: value as FormDataConvertible }) : query,
        {} as Record<string, FormDataConvertible>,
    );
}

export function useFilters<TForm extends FormDataType<{ [K in keyof TForm]: TForm[K] | undefined }>>(
    urlMethodPair: UrlMethodPair | (() => UrlMethodPair),
    data: TForm | (() => TForm),
): InertiaForm<TForm> {
    let recentlySuccessfulTimeoutId: ReturnType<typeof setTimeout> | undefined;

    // If we call reset later we want to clear all fields, so we initialize them with undefined
    const filters = useForm<TForm>(
        Object.keys(toValue(data)).reduce(
            (carry, key) => Object.assign(carry, { [key]: undefined }),
            {} as Partial<TForm>,
        ) as TForm,
    );

    // Must be reactive to update storage if changed
    const transform = ref<UseFormTransformCallback<TForm>>((data) => data);
    filters.transform = (callback) => {
        transform.value = callback;

        return filters;
    };

    const storageKey = toValue(urlMethodPair).url.split('?')[0];

    // Restore previous values from session and keep in sync
    // Link storage key to given URL without query params
    const remembered = useSessionStorage<Partial<TForm>>(
        storageKey,
        {},
        {
            mergeDefaults: true,
        },
    );

    // Allow clearing filters via URL param
    const urlParams = useUrlSearchParams('history');

    if (urlParams.reset_filters) {
        remembered.value = {};
    }

    Object.assign(filters, {
        ...remembered.value,
    });
    syncRefs(() => filters.data(), remembered, {
        deep: true,
        immediate: true,
    });

    // Handle form submission manually to add some default behavior
    filters.submit = (...args) => {
        const { options } = UseFormUtils.parseSubmitArguments(args, () => toValue(urlMethodPair));
        const query = filledQueryValues(transform.value(filters.data()));

        router.visit(toValue(urlMethodPair), {
            data: query,
            preserveScroll: true,
            preserveState: true,
            replace: true,
            ...options,
            onBefore: (visit) => {
                filters.wasSuccessful = false;
                filters.recentlySuccessful = false;
                clearTimeout(recentlySuccessfulTimeoutId);

                return options.onBefore?.(visit);
            },
            onStart: (visit) => {
                filters.processing = true;

                return options.onStart?.(visit);
            },
            onProgress: (event) => {
                filters.progress = event ?? null;

                return options.onProgress?.(event);
            },
            onSuccess: (page) => {
                filters.clearErrors();
                filters.wasSuccessful = true;
                filters.recentlySuccessful = true;
                recentlySuccessfulTimeoutId = setTimeout(() => {
                    filters.recentlySuccessful = false;
                }, 2000);

                return options.onSuccess?.(page);
            },
            onError: (errors) => {
                filters.clearErrors().setError(errors as FormDataErrors<TForm>);

                return options.onError?.(errors);
            },
            onFinish: (visit) => {
                filters.processing = false;
                filters.progress = null;

                return options.onFinish?.(visit);
            },
        });
    };

    return filters;
}
