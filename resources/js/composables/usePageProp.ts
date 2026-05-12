import type { CancelToken, ReloadOptions } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/vue3';
import { useModal } from '@inertiaui/modal-vue';
import { reactiveOmit } from '@vueuse/core';
import { get } from 'es-toolkit/compat';
import type { MaybeRefOrGetter } from 'vue';
import { computed, onMounted, reactive, shallowRef, toValue, watch } from 'vue';

type ReloadInit = ReloadOptions & { immediate?: boolean };

type UsePagePropOptions<T> =
    | (ReloadInit & { required: true })
    | (ReloadInit & { default: MaybeRefOrGetter<T> })
    | ReloadInit;

type UsePagePropReturn<T> = {
    value: T;
    loading: boolean;
    reload: (override?: ReloadOptions) => void;
};

export function usePageProp<T = unknown>(key: MaybeRefOrGetter<string>): UsePagePropReturn<T | undefined>;
export function usePageProp<T = unknown>(
    key: MaybeRefOrGetter<string>,
    options: ReloadInit & { required: true },
): UsePagePropReturn<T>;
export function usePageProp<T>(
    key: MaybeRefOrGetter<string>,
    options: ReloadInit & { default: MaybeRefOrGetter<T> },
): UsePagePropReturn<T>;
export function usePageProp<T = unknown>(
    key: MaybeRefOrGetter<string>,
    options: UsePagePropOptions<T>,
): UsePagePropReturn<T | undefined>;
export function usePageProp<T>(key: MaybeRefOrGetter<string>, options: UsePagePropOptions<T> = {} as ReloadInit) {
    const page = usePage();
    const modal = useModal();

    const reloadOptions = reactiveOmit(
        options as ReloadInit & Record<string, unknown>,
        'required',
        'default',
        'immediate',
    );

    const loading = shallowRef(false);
    let cancelToken: CancelToken | undefined;

    const value = computed(() => {
        const dataKey = toValue(key);

        const modalValue = modal?.props ? get(modal.props, dataKey) : undefined;

        if (modalValue !== undefined) {
            return modalValue as T;
        }

        const pageValue = get(page.props, dataKey);

        if (pageValue !== undefined) {
            return pageValue as T;
        }

        if ('required' in options && options.required) {
            throw new Error(`usePageProp: required page prop "${dataKey}" is undefined`);
        }

        if ('default' in options) {
            return toValue(options.default);
        }

        return undefined;
    });

    function reload(override?: ReloadOptions) {
        if (typeof window === 'undefined') {
            return;
        }

        const dataKey = toValue(key);

        if (!dataKey) {
            return;
        }

        cancelToken?.cancel();

        const merged: ReloadOptions = {
            only: [dataKey],
            ...reloadOptions,
            ...override,
        };

        const callerOnCancelToken = merged.onCancelToken;
        const callerOnFinish = merged.onFinish;

        loading.value = true;

        const wrapped: ReloadOptions = {
            ...merged,
            onCancelToken: (token) => {
                cancelToken = token;
                callerOnCancelToken?.(token);
            },
            onFinish: (visit) => {
                callerOnFinish?.(visit);
                loading.value = false;
                cancelToken = undefined;
            },
        };

        if (modal) {
            modal.reload(wrapped as Parameters<typeof modal.reload>[0]);
        } else {
            router.reload(wrapped);
        }
    }

    function maybeAutoReload() {
        if ('required' in options && options.required) {
            return;
        }

        const immediate = 'immediate' in options && options.immediate === true;
        const hasReloadOptions = Object.keys(reloadOptions).length > 0;

        if (!immediate && !hasReloadOptions) {
            return;
        }

        const dataKey = toValue(key);
        const modalValue = modal?.props ? get(modal.props, dataKey) : undefined;
        const pageValue = get(page.props, dataKey);

        if (modalValue === undefined && pageValue === undefined) {
            reload();
        }
    }

    onMounted(maybeAutoReload);

    watch(
        () => toValue(key),
        () => maybeAutoReload(),
    );

    return reactive({
        value,
        loading,
        reload,
    }) as UsePagePropReturn<T>;
}
