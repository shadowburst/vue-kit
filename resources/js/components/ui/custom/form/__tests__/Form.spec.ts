import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h, ref } from 'vue';
import type { FormContext } from '../context';
import { injectFormContext } from '../context';
import FormImpl from '../Form.vue';

// The Form generic over TData blows up vue-tsc's type instantiation depth when
// passed straight to @vue/test-utils' mount; cast away the generic for tests.
const Form = FormImpl as never as new () => { $props: Record<string, unknown> };

type StubForm = {
    submit: ReturnType<typeof vi.fn>;
};

function createStubForm(): StubForm {
    return { submit: vi.fn() };
}

describe('Form', () => {
    it('renders a form element with data-disabled when disabled', () => {
        const wrapper = mount(Form, {
            props: { disabled: true },
            slots: { default: () => 'content' },
        });

        expect(wrapper.find('form').attributes('data-disabled')).toBe('true');
    });

    it('emits submit and calls form.submit with action+options when both are provided', async () => {
        const stub = createStubForm();
        const action = { url: '/profile', method: 'patch' as const };
        const options = { preserveScroll: true };

        const wrapper = mount(Form, {
            props: { form: stub as never, action, options },
        });

        await wrapper.find('form').trigger('submit');

        expect(wrapper.emitted('submit')).toHaveLength(1);
        expect(stub.submit).toHaveBeenCalledWith(action, options);
    });

    it('passes method, url, options when action is a string', async () => {
        const stub = createStubForm();

        const wrapper = mount(Form, {
            props: { form: stub as never, action: '/profile', method: 'patch' },
        });

        await wrapper.find('form').trigger('submit');

        expect(stub.submit).toHaveBeenCalledWith('patch', '/profile', undefined);
    });

    it('defaults string-action method to post', async () => {
        const stub = createStubForm();

        const wrapper = mount(Form, {
            props: { form: stub as never, action: '/profile' },
        });

        await wrapper.find('form').trigger('submit');

        expect(stub.submit).toHaveBeenCalledWith('post', '/profile', undefined);
    });

    it('emits submit but does not call form.submit when no action is set', async () => {
        const stub = createStubForm();

        const wrapper = mount(Form, {
            props: { form: stub as never },
        });

        await wrapper.find('form').trigger('submit');

        expect(wrapper.emitted('submit')).toHaveLength(1);
        expect(stub.submit).not.toHaveBeenCalled();
    });

    it('emits submit when no form is set', async () => {
        const wrapper = mount(Form, {
            props: { action: { url: '/x', method: 'post' as const } },
        });

        await wrapper.find('form').trigger('submit');

        expect(wrapper.emitted('submit')).toHaveLength(1);
    });

    it('blocks emit and form.submit when disabled', async () => {
        const stub = createStubForm();

        const wrapper = mount(Form, {
            props: {
                form: stub as never,
                action: { url: '/x', method: 'post' as const },
                disabled: true,
            },
        });

        await wrapper.find('form').trigger('submit');

        expect(wrapper.emitted('submit')).toBeUndefined();
        expect(stub.submit).not.toHaveBeenCalled();
    });

    it('blocks emit and form.submit when canSubmit is false', async () => {
        const stub = createStubForm();

        const wrapper = mount(Form, {
            props: {
                form: stub as never,
                action: { url: '/x', method: 'post' as const },
                canSubmit: false,
            },
        });

        await wrapper.find('form').trigger('submit');

        expect(wrapper.emitted('submit')).toBeUndefined();
        expect(stub.submit).not.toHaveBeenCalled();
    });

    it('exposes form, disabled, canSubmit as slot props mirroring the context', () => {
        const stub = createStubForm();
        let captured: Record<string, unknown> | undefined;

        mount(
            defineComponent({
                setup: () => ({ stub }),
                render() {
                    return h(Form, { form: this.stub, disabled: true, canSubmit: false }, {
                        default: (slotProps: Record<string, unknown>) => {
                            captured = slotProps;

                            return h('span');
                        },
                    });
                },
            }),
        );

        expect(captured?.form).toBe(stub);
        expect(captured?.disabled).toBe(true);
        expect(captured?.canSubmit).toBe(false);
    });

    it('reflects prop changes through the context form getter', async () => {
        const initial = { ...createStubForm(), tag: 'initial' };
        const next = { ...createStubForm(), tag: 'next' };
        const formRef = ref<typeof initial | undefined>(initial);

        const Child = defineComponent({
            setup() {
                const ctx = injectFormContext<{ tag: string }>();

                return () => h('span', { 'data-form': ctx.form?.tag ?? 'none' });
            },
        });

        const wrapper = mount(
            defineComponent({
                components: { FormRoot: Form, Child },
                setup: () => ({ formRef }),
                template: `<FormRoot :form="formRef"><Child /></FormRoot>`,
            }),
        );

        expect(wrapper.find('span').attributes('data-form')).toBe('initial');

        formRef.value = next;
        await wrapper.vm.$nextTick();

        expect(wrapper.find('span').attributes('data-form')).toBe('next');
    });
});

describe('injectFormContext', () => {
    function mountChild(child: ReturnType<typeof defineComponent>, formProps: Record<string, unknown> = {}) {
        return mount(
            defineComponent({
                setup: () => ({ formProps }),
                render() {
                    return h(Form, this.formProps, { default: () => h(child) });
                },
            }),
        );
    }

    it('throws when called outside a Form ancestor', () => {
        const Child = defineComponent({
            setup() {
                injectFormContext();

                return () => h('span');
            },
        });

        expect(() => mount(Child)).toThrow(/inside a <Form> ancestor/);
    });

    it('returns the context with a possibly-undefined form by default', () => {
        let captured: FormContext<object> | undefined;
        const Child = defineComponent({
            setup() {
                captured = injectFormContext();

                return () => h('span');
            },
        });

        mountChild(Child);

        expect(captured).toBeDefined();
        expect(captured?.form).toBeUndefined();
        expect(captured?.disabled.value).toBe(false);
        expect(captured?.canSubmit.value).toBe(true);
    });

    it('throws when required:true and no form was provided', () => {
        const Child = defineComponent({
            setup() {
                injectFormContext({ required: true });

                return () => h('span');
            },
        });

        expect(() => mountChild(Child)).toThrow(/required: true/);
    });

    it('returns the form when required:true and a form was provided', () => {
        const stub = createStubForm();
        let captured: ReturnType<typeof injectFormContext> | undefined;

        const Child = defineComponent({
            setup() {
                captured = injectFormContext({ required: true });

                return () => h('span');
            },
        });

        mountChild(Child, { form: stub });

        expect(captured?.form).toBe(stub);
    });

    it('falls back to default when no form is provided', () => {
        const fallback = createStubForm();
        let captured: ReturnType<typeof injectFormContext> | undefined;

        const Child = defineComponent({
            setup() {
                captured = injectFormContext({ default: fallback as never });

                return () => h('span');
            },
        });

        mountChild(Child);

        expect(captured?.form).toBe(fallback);
    });

    it('returns the provided form over the default when one exists', () => {
        const provided = createStubForm();
        const fallback = createStubForm();
        let captured: ReturnType<typeof injectFormContext> | undefined;

        const Child = defineComponent({
            setup() {
                captured = injectFormContext({ default: fallback as never });

                return () => h('span');
            },
        });

        mountChild(Child, { form: provided });

        expect(captured?.form).toBe(provided);
    });
});
