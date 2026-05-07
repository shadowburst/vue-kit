import { createContext } from 'reka-ui';
import type { ComputedRef } from 'vue';

export type SectionSize = 'default' | 'sm';

export interface SectionContext {
    size: ComputedRef<SectionSize>;
}

export const [injectSectionContext, provideSectionContext] = createContext<SectionContext>('Section');
