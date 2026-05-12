import type { VariantProps } from 'class-variance-authority';
import { cva } from 'class-variance-authority';

export { default as InertiaLink } from './InertiaLink.vue';

export const inertiaLinkVariants = cva('', {
    variants: {
        variant: {
            default: '',
            text: 'rounded-sm text-foreground underline decoration-muted-foreground underline-offset-4 ring-offset-background transition-colors duration-300 ease-out hover:decoration-current! focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-hidden enabled:cursor-pointer',
        },
    },
    defaultVariants: {
        variant: 'default',
    },
});
export type InertiaLinkVariants = VariantProps<typeof inertiaLinkVariants>;
