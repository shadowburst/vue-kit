<script setup lang="ts">
import { Field as ShadcnField } from '@/components/ui/field';
import type { FieldVariants } from '@/components/ui/field';
import type { HTMLAttributes } from 'vue';
import { computed, ref, useId } from 'vue';
import { provideFieldContext } from './context';

const props = withDefaults(
    defineProps<{
        id?: string;
        required?: boolean;
        disabled?: boolean;
        orientation?: FieldVariants['orientation'];
        class?: HTMLAttributes['class'];
    }>(),
    {
        required: false,
        disabled: false,
        orientation: 'vertical',
    },
);

const fallbackId = useId();
const id = props.id ?? fallbackId;
const required = computed(() => props.required);
const disabled = computed(() => props.disabled);

const descriptionId = ref<string | undefined>(undefined);
const errorId = ref<string | undefined>(undefined);
const invalid = computed(() => !!errorId.value);

provideFieldContext({
    id,
    required,
    disabled,
    descriptionId,
    errorId,
    setDescriptionId: (next) => {
        descriptionId.value = next;
    },
    setErrorId: (next) => {
        errorId.value = next;
    },
    invalid,
});
</script>

<template>
    <ShadcnField
        :orientation="orientation"
        :data-disabled="disabled ? 'true' : undefined"
        :data-required="required ? 'true' : undefined"
        :data-invalid="invalid ? 'true' : undefined"
        :class="props.class"
    >
        <slot />
    </ShadcnField>
</template>
