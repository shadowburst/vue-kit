import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { defineComponent, h, nextTick, ref } from 'vue';
import Field from '../Field.vue';
import FieldControl from '../FieldControl.vue';
import FieldDescription from '../FieldDescription.vue';
import FieldError from '../FieldError.vue';
import FieldLabel from '../FieldLabel.vue';

function mountField(slot: () => unknown, props: Record<string, unknown> = {}) {
    return mount(
        defineComponent({
            components: { Field },
            setup: () => ({ props, slot }),
            render() {
                return h(Field, props, { default: slot });
            },
        }),
    );
}

describe('Field', () => {
    it('allocates an id and threads it to FieldLabel for and FieldControl id', () => {
        const wrapper = mountField(() => [
            h(FieldLabel, () => 'Email'),
            h(FieldControl, () => h('input', { type: 'email' })),
        ]);

        const input = wrapper.get('input');
        const label = wrapper.get('label');
        const id = input.attributes('id');

        expect(id).toBeTruthy();
        expect(label.attributes('for')).toBe(id);
    });

    it('uses an explicit prop id over the auto-generated fallback', () => {
        const wrapper = mountField(() => [h(FieldLabel, () => 'Name'), h(FieldControl, () => h('input'))], {
            id: 'name-field',
        });

        expect(wrapper.get('input').attributes('id')).toBe('name-field');
        expect(wrapper.get('label').attributes('for')).toBe('name-field');
    });

    it('lets FieldLabel for and FieldControl id override the context', () => {
        const wrapper = mountField(
            () => [
                h(FieldLabel, { for: 'override-label' }, () => 'Custom'),
                h(FieldControl, { id: 'override-control' }, () => h('input')),
            ],
            { id: 'context-id' },
        );

        expect(wrapper.get('label').attributes('for')).toBe('override-label');
        expect(wrapper.get('input').attributes('id')).toBe('override-control');
    });

    it('propagates required and disabled to the slot child and the root data-*', () => {
        const wrapper = mountField(() => [h(FieldLabel, () => 'X'), h(FieldControl, () => h('input'))], {
            required: true,
            disabled: true,
        });

        const input = wrapper.get('input');
        const root = wrapper.find('[data-slot="field"]');

        expect(input.attributes('required')).toBeDefined();
        expect(input.attributes('disabled')).toBeDefined();
        expect(root.attributes('data-required')).toBe('true');
        expect(root.attributes('data-disabled')).toBe('true');
    });

    it('renders a required asterisk inside FieldLabel when required', () => {
        const wrapper = mountField(() => [h(FieldLabel, () => 'X'), h(FieldControl, () => h('input'))], {
            required: true,
        });

        expect(wrapper.get('label').html()).toContain('aria-hidden="true"');
        expect(wrapper.get('label').text()).toContain('*');
    });

    it('builds aria-describedby from FieldDescription + FieldError ids', async () => {
        const wrapper = mountField(() => [
            h(FieldDescription, { id: 'desc-1' }, () => 'Help'),
            h(FieldControl, () => h('input')),
            h(FieldError, { id: 'err-1' }, () => 'Bad'),
        ]);

        await nextTick();

        expect(wrapper.get('input').attributes('aria-describedby')).toBe('desc-1 err-1');
        expect(wrapper.get('input').attributes('aria-invalid')).toBe('true');
        expect(wrapper.find('[data-slot="field"]').attributes('data-invalid')).toBe('true');
    });

    it('appends explicit aria-describedby on FieldControl to the derived ids', async () => {
        const wrapper = mountField(() => [
            h(FieldDescription, { id: 'desc-2' }, () => 'Help'),
            h(FieldControl, { 'aria-describedby': 'extra-id' }, () => h('input')),
        ]);

        await nextTick();

        expect(wrapper.get('input').attributes('aria-describedby')).toBe('desc-2 extra-id');
    });

    it('omits aria-describedby and aria-invalid when no description/error mounted', () => {
        const wrapper = mountField(() => [h(FieldControl, () => h('input'))]);
        const input = wrapper.get('input');

        expect(input.attributes('aria-describedby')).toBeUndefined();
        expect(input.attributes('aria-invalid')).toBeUndefined();
    });

    it('flips aria-invalid reactively when FieldError mounts and unmounts', async () => {
        const showError = ref(false);
        const wrapper = mount(
            defineComponent({
                components: { Field, FieldControl, FieldError },
                setup: () => ({ showError }),
                template: `
                    <Field>
                        <FieldControl><input /></FieldControl>
                        <FieldError v-if="showError" id="dyn-err">Oops</FieldError>
                    </Field>
                `,
            }),
        );

        expect(wrapper.get('input').attributes('aria-invalid')).toBeUndefined();

        showError.value = true;
        await nextTick();

        expect(wrapper.get('input').attributes('aria-invalid')).toBe('true');
        expect(wrapper.get('input').attributes('aria-describedby')).toBe('dyn-err');

        showError.value = false;
        await nextTick();

        expect(wrapper.get('input').attributes('aria-invalid')).toBeUndefined();
        expect(wrapper.get('input').attributes('aria-describedby')).toBeUndefined();
    });

    it('does not register a FieldError that has no slot content or messages', () => {
        const wrapper = mountField(() => [h(FieldControl, () => h('input')), h(FieldError, { errors: [] })]);

        expect(wrapper.get('input').attributes('aria-invalid')).toBeUndefined();
        expect(wrapper.find('[data-slot="field-error"]').exists()).toBe(false);
    });
});

describe('Sub-components outside Field', () => {
    it('FieldLabel renders with explicit for and required outside a Field', () => {
        const wrapper = mount(FieldLabel, {
            props: { for: 'standalone', required: true },
            slots: { default: 'Standalone' },
        });

        expect(wrapper.get('label').attributes('for')).toBe('standalone');
        expect(wrapper.get('label').text()).toContain('*');
    });

    it('FieldControl is an inert Slot outside a Field', () => {
        const wrapper = mount({
            components: { FieldControl },
            template: `<FieldControl><input data-test="naked" /></FieldControl>`,
        });

        const input = wrapper.get('input');

        expect(input.attributes('data-test')).toBe('naked');
        expect(input.attributes('id')).toBeUndefined();
        expect(input.attributes('aria-describedby')).toBeUndefined();
        expect(input.attributes('aria-invalid')).toBeUndefined();
    });

    it('FieldDescription renders content without registering anywhere', () => {
        const wrapper = mount(FieldDescription, {
            props: { id: 'lone-desc' },
            slots: { default: 'Standalone help' },
        });

        const node = wrapper.find('[data-slot="field-description"]');

        expect(node.exists()).toBe(true);
        expect(node.attributes('id')).toBe('lone-desc');
    });

    it('FieldError renders content without registering anywhere', () => {
        const wrapper = mount(FieldError, {
            props: { id: 'lone-err' },
            slots: { default: 'Standalone error' },
        });

        const node = wrapper.find('[data-slot="field-error"]');

        expect(node.exists()).toBe(true);
        expect(node.attributes('id')).toBe('lone-err');
    });
});
