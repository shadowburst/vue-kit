import { createContext } from 'reka-ui';
import type { ComputedRef, Ref } from 'vue';

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
