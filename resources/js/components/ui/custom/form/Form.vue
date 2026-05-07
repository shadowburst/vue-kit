<script setup lang="ts" generic="TData extends object = Record<string, FormDataConvertible>">
import type { FormDataConvertible, Method, UrlMethodPair, UseFormSubmitOptions } from '@inertiajs/core';
import type { InertiaForm, InertiaPrecognitiveForm } from '@inertiajs/vue3';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { provideFormContext } from './context';

const props = withDefaults(
    defineProps<{
        form?: InertiaForm<TData> | InertiaPrecognitiveForm<TData>;
        action?: string | UrlMethodPair;
        method?: Method;
        options?: UseFormSubmitOptions;
        disabled?: boolean;
        canSubmit?: boolean;
        class?: HTMLAttributes['class'];
    }>(),
    {
        disabled: false,
        canSubmit: true,
    },
);

const emit = defineEmits<{
    submit: [event: SubmitEvent];
}>();

const disabled = computed(() => props.disabled);
const canSubmit = computed(() => props.canSubmit);

provideFormContext<TData>({
    get form() {
        return props.form;
    },
    disabled,
    canSubmit,
});

function onSubmit(event: SubmitEvent): void {
    if (props.disabled || !props.canSubmit) {
        return;
    }

    emit('submit', event);

    if (!props.form || props.action === undefined) {
        return;
    }

    if (typeof props.action === 'string') {
        props.form.submit(props.method ?? 'post', props.action, props.options);
    } else {
        props.form.submit(props.action, props.options);
    }
}

defineSlots<{
    default: (slotProps: {
        form: InertiaForm<TData> | InertiaPrecognitiveForm<TData> | undefined;
        disabled: boolean;
        canSubmit: boolean;
    }) => unknown;
}>();
</script>

<template>
    <form
        :data-disabled="disabled ? 'true' : undefined"
        :class="props.class"
        @submit.prevent="onSubmit"
    >
        <slot :form="props.form" :disabled="disabled" :can-submit="canSubmit" />
    </form>
</template>
