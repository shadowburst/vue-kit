import type { FormDataConvertible } from '@inertiajs/core';
import type { InertiaForm, InertiaPrecognitiveForm } from '@inertiajs/vue3';
import { createContext } from 'reka-ui';
import type { ComputedRef, InjectionKey, Ref } from 'vue';
import { inject, provide } from 'vue';

export interface FieldContext {
    id: string;
    required: ComputedRef<boolean>;
    disabled: ComputedRef<boolean>;
    descriptionId: Ref<string | undefined>;
    errorId: Ref<string | undefined>;
    setDescriptionId: (id: string | undefined) => void;
    setErrorId: (id: string | undefined) => void;
    invalid: ComputedRef<boolean>;
}

export const [injectFieldContext, provideFieldContext] = createContext<FieldContext>('Field');

export interface FormContext<TData extends object = Record<string, FormDataConvertible>> {
    form: InertiaForm<TData> | InertiaPrecognitiveForm<TData> | undefined;
    disabled: ComputedRef<boolean>;
    canSubmit: ComputedRef<boolean>;
}

const FORM_CONTEXT_KEY = Symbol('FormContext') as InjectionKey<FormContext<any>>;

export function provideFormContext<TData extends object>(ctx: FormContext<TData>): void {
    provide(FORM_CONTEXT_KEY, ctx);
}

type InjectFormContextRequiredOptions = { required: true; default?: never };
type InjectFormContextDefaultOptions<TData extends object> = {
    required?: never;
    default: InertiaForm<TData> | InertiaPrecognitiveForm<TData>;
};

type ResolvedFormContext<TData extends object> = FormContext<TData> & {
    form: InertiaForm<TData> | InertiaPrecognitiveForm<TData>;
};

export function injectFormContext<TData extends object = Record<string, FormDataConvertible>>(): FormContext<TData>;
export function injectFormContext<TData extends object>(
    opts: InjectFormContextRequiredOptions,
): ResolvedFormContext<TData>;
export function injectFormContext<TData extends object>(
    opts: InjectFormContextDefaultOptions<TData>,
): ResolvedFormContext<TData>;
export function injectFormContext<TData extends object>(opts?: {
    required?: true;
    default?: InertiaForm<TData> | InertiaPrecognitiveForm<TData>;
}): FormContext<TData> {
    const ctx = inject<FormContext<TData> | undefined>(FORM_CONTEXT_KEY, undefined);

    if (!ctx) {
        throw new Error('injectFormContext() must be called inside a <Form> ancestor.');
    }

    if (opts?.required && ctx.form === undefined) {
        throw new Error('injectFormContext({ required: true }) called but no form was provided to <Form>.');
    }

    if (opts?.default !== undefined && ctx.form === undefined) {
        return { ...ctx, form: opts.default };
    }

    return ctx;
}
