import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { defineComponent, h } from 'vue';
import { __resetSmartDialogDepthForTests, useSmartDialogDepth } from '../useSmartDialogDepth';

const Probe = defineComponent({
    name: 'DepthProbe',
    setup(_, { expose }) {
        const { depth, zIndex } = useSmartDialogDepth();
        expose({ depth, zIndex });

        return () => h('div');
    },
});

beforeEach(() => {
    __resetSmartDialogDepthForTests();
});

describe('useSmartDialogDepth', () => {
    it('first mount captures depth 0 with zIndex 50', () => {
        const wrapper = mount(Probe);

        expect(wrapper.vm.depth).toBe(0);
        expect(wrapper.vm.zIndex).toBe(50);
    });

    it('second mount captures depth 1 with zIndex 51', () => {
        const outer = mount(Probe);
        const inner = mount(Probe);

        expect(outer.vm.depth).toBe(0);
        expect(outer.vm.zIndex).toBe(50);
        expect(inner.vm.depth).toBe(1);
        expect(inner.vm.zIndex).toBe(51);
    });

    it('LIFO unmount returns the counter to 0 so the next mount is depth 0', () => {
        const outer = mount(Probe);
        const inner = mount(Probe);

        inner.unmount();
        outer.unmount();

        const next = mount(Probe);

        expect(next.vm.depth).toBe(0);
        expect(next.vm.zIndex).toBe(50);
    });

    it('three-deep nesting stacks at zIndex 50, 51, 52', () => {
        const a = mount(Probe);
        const b = mount(Probe);
        const c = mount(Probe);

        expect(a.vm.zIndex).toBe(50);
        expect(b.vm.zIndex).toBe(51);
        expect(c.vm.zIndex).toBe(52);
    });
});
