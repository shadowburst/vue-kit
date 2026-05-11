import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h, nextTick, ref } from 'vue';

const pageState = { props: {} as Record<string, unknown> };
const modalState: { props: Record<string, unknown> | null; reload: ReturnType<typeof vi.fn> } = {
    props: null,
    reload: vi.fn(),
};
const routerReload = vi.fn();

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => pageState,
    router: {
        reload: (...args: unknown[]) => routerReload(...args),
    },
}));

vi.mock('@inertiaui/modal-vue', () => ({
    useModal: () => (modalState.props === null ? null : { props: modalState.props, reload: modalState.reload }),
}));

import { usePageProp } from '../usePageProp';

beforeEach(() => {
    pageState.props = {};
    modalState.props = null;
    modalState.reload.mockReset();
    routerReload.mockReset();
});

function mountWith<T>(setup: () => T) {
    let captured!: T;
    const Comp = defineComponent({
        setup() {
            captured = setup();
            return () => h('div');
        },
    });
    const wrapper = mount(Comp);
    return { result: captured, wrapper };
}

describe('usePageProp', () => {
    it('returns undefined when the key is absent and no options are passed', () => {
        const value = usePageProp<string>('missing');

        expect(value.value).toBeUndefined();
    });

    it('returns the page prop when present', () => {
        pageState.props = { user: { name: 'Ada' } };
        const value = usePageProp<string>('user.name');

        expect(value.value).toBe('Ada');
    });

    it('prefers modal props over page props', () => {
        pageState.props = { user: { name: 'Ada' } };
        modalState.props = { user: { name: 'Grace' } };

        const value = usePageProp<string>('user.name');

        expect(value.value).toBe('Grace');
    });

    it('returns null values as-is and does not fall through to the default', () => {
        pageState.props = { avatar: null };
        const value = usePageProp<string | null>('avatar', { default: '/fallback.png' });

        expect(value.value).toBeNull();
    });

    it('falls back to the default value when the prop is undefined', () => {
        const value = usePageProp<number>('count', { default: 7 });

        expect(value.value).toBe(7);
    });

    it('resolves a ref default lazily', () => {
        const fallback = ref('a');
        const value = usePageProp<string>('missing', { default: fallback });

        expect(value.value).toBe('a');
        fallback.value = 'b';
        expect(value.value).toBe('b');
    });

    it('resolves a getter default reactively', () => {
        const n = ref(1);
        const value = usePageProp<number>('missing', { default: () => n.value });

        expect(value.value).toBe(1);
        n.value = 2;
        expect(value.value).toBe(2);
    });

    it('throws when required and the prop is undefined', () => {
        const value = usePageProp<string>('members', { required: true });

        expect(() => value.value).toThrowError(/members/);
    });

    it('does not throw when required and the prop is present', () => {
        pageState.props = { members: ['a', 'b'] };
        const value = usePageProp<string[]>('members', { required: true });

        expect(value.value).toEqual(['a', 'b']);
    });

    it('reacts to a key passed as a getter', () => {
        pageState.props = { a: 1, b: 2 };
        const key = ref<'a' | 'b'>('a');
        const value = usePageProp<number>(() => key.value);

        expect(value.value).toBe(1);
        key.value = 'b';
        expect(value.value).toBe(2);
    });

    describe('auto-reload', () => {
        it('does not auto-reload when no reload options or immediate are passed', async () => {
            mountWith(() => usePageProp<string>('missing', { default: 'x' }));
            await nextTick();

            expect(routerReload).not.toHaveBeenCalled();
        });

        it('auto-reloads on mount when reload options are passed and the value is undefined', async () => {
            mountWith(() => usePageProp<string>('users', { only: ['users'] }));
            await nextTick();

            expect(routerReload).toHaveBeenCalledTimes(1);
            expect(routerReload.mock.calls[0][0]).toMatchObject({ only: ['users'] });
        });

        it('does not auto-reload when the value is already defined', async () => {
            pageState.props = { users: [{ id: 1 }] };
            mountWith(() => usePageProp<unknown[]>('users', { only: ['users'] }));
            await nextTick();

            expect(routerReload).not.toHaveBeenCalled();
        });

        it('auto-reloads on mount when immediate is true even if value is defined', async () => {
            pageState.props = { users: [{ id: 1 }] };
            mountWith(() => usePageProp<unknown[]>('users', { immediate: true }));
            await nextTick();

            expect(routerReload).toHaveBeenCalledTimes(1);
        });

        it('defaults only to the data key when not overridden', async () => {
            mountWith(() => usePageProp<string>('foo', { immediate: true }));
            await nextTick();

            expect(routerReload.mock.calls[0][0]).toMatchObject({ only: ['foo'] });
        });

        it('does not auto-reload when required even if the value is undefined', async () => {
            mountWith(() => usePageProp<string>('missing', { required: true, only: ['missing'] }));
            await nextTick();

            expect(routerReload).not.toHaveBeenCalled();
        });

        it('returns the default while a reload is in flight', async () => {
            const { result } = mountWith(() => usePageProp<string[]>('users', { default: [], immediate: true }));
            await nextTick();

            expect(routerReload).toHaveBeenCalledTimes(1);
            expect(result.value).toEqual([]);
            expect(result.loading).toBe(true);
        });

        it('skips reload when the resolved key is falsy', async () => {
            mountWith(() => usePageProp<unknown[]>(() => '', { immediate: true }));
            await nextTick();

            expect(routerReload).not.toHaveBeenCalled();
        });

        it('refires reload when the key changes', async () => {
            const key = ref('a');
            mountWith(() => usePageProp<string>(() => key.value, { only: ['will-be-replaced'] }));
            await nextTick();

            expect(routerReload).toHaveBeenCalledTimes(1);

            key.value = 'b';
            await nextTick();
            await nextTick();

            expect(routerReload).toHaveBeenCalledTimes(2);
        });

        it('routes the reload through the modal when a modal is in scope', async () => {
            modalState.props = {};
            mountWith(() => usePageProp<unknown[]>('users', { immediate: true }));
            await nextTick();

            expect(modalState.reload).toHaveBeenCalledTimes(1);
            expect(routerReload).not.toHaveBeenCalled();
        });
    });

    describe('manual reload', () => {
        it('shallow-merges override options over the initial reload options', () => {
            const { result } = mountWith(() =>
                usePageProp<unknown[]>('users', { only: ['users'], headers: { 'X-Init': '1' } }),
            );

            routerReload.mockReset();
            result.reload({ headers: { 'X-Override': '1' } });

            expect(routerReload.mock.calls[0][0]).toMatchObject({
                only: ['users'],
                headers: { 'X-Override': '1' },
            });
        });

        it('flips loading true while in-flight and false on onFinish', () => {
            const { result } = mountWith(() => usePageProp<unknown[]>('users', { default: [] }));

            expect(result.loading).toBe(false);
            result.reload();
            expect(result.loading).toBe(true);

            const passed = routerReload.mock.calls[0][0];
            passed.onFinish?.({});

            expect(result.loading).toBe(false);
        });

        it('cancels an in-flight reload when reload is called again', () => {
            const { result } = mountWith(() => usePageProp<unknown[]>('users', { default: [] }));

            const cancel = vi.fn();
            result.reload();
            routerReload.mock.calls[0][0].onCancelToken({ cancel });

            result.reload();

            expect(cancel).toHaveBeenCalledTimes(1);
        });

        it('chains the caller onFinish before clearing loading', () => {
            const { result } = mountWith(() => usePageProp<unknown[]>('users', { default: [] }));
            const onFinish = vi.fn();

            result.reload({ onFinish });
            routerReload.mock.calls[0][0].onFinish({});

            expect(onFinish).toHaveBeenCalledTimes(1);
            expect(result.loading).toBe(false);
        });
    });
});
