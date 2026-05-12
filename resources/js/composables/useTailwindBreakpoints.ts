import { breakpointsTailwind, createSharedComposable, useBreakpoints } from '@vueuse/core';

export const useTailwindBreakpoints = createSharedComposable(() => useBreakpoints(breakpointsTailwind));
