<script setup lang="ts">
import { Slot } from 'reka-ui';
import { computed, useAttrs } from 'vue';
import { injectFieldContext } from './context';
import { reactiveOmit } from '@vueuse/core';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        id?: string;
        required?: boolean;
        disabled?: boolean;
    }>(),
    {
        id: undefined,
        required: undefined,
        disabled: undefined,
    },
);

const attrs = useAttrs();
const ctx = injectFieldContext(null);

const resolvedId = computed(() => props.id ?? ctx?.id);
const resolvedRequired = computed(() => props.required ?? ctx?.required.value ?? false);
const resolvedDisabled = computed(() => props.disabled ?? ctx?.disabled.value ?? false);

const resolvedDescribedBy = computed(() => {
    const ids: string[] = [];

    if (ctx?.descriptionId.value) {
        ids.push(ctx.descriptionId.value);
    }

    if (ctx?.errorId.value) {
        ids.push(ctx.errorId.value);
    }

    const explicit = attrs['aria-describedby'];

    if (typeof explicit === 'string' && explicit.length > 0) {
        ids.push(explicit);
    }

    return ids.length > 0 ? ids.join(' ') : undefined;
});

const resolvedInvalid = computed(() => {
    const explicit = attrs['aria-invalid'];

    if (explicit !== undefined) {
        return explicit as boolean | string;
    }

    return ctx?.invalid.value ? true : undefined;
});

const passthroughAttrs = reactiveOmit(attrs, 'aria-describedby', 'aria-invalid')
</script>

<template>
    <Slot
        v-bind="passthroughAttrs"
        :id="resolvedId"
        :required="resolvedRequired || undefined"
        :disabled="resolvedDisabled || undefined"
        :aria-describedby="resolvedDescribedBy"
        :aria-invalid="resolvedInvalid"
    >
        <slot />
    </Slot>
</template>
