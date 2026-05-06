import type { ButtonVariants } from '@/components/ui/button';
import type { LucideIcon } from '@lucide/vue';
import type { Component, HTMLAttributes, MaybeRefOrGetter } from 'vue';

type BaseActionItem = {
    label: string;
    icon: LucideIcon | Component;
    disabled?: MaybeRefOrGetter<boolean>;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    class?: HTMLAttributes['class'];
};
export type HrefActionItem = BaseActionItem & {
    href: MaybeRefOrGetter<string>;
    callback?: never;
};
export type CallbackActionItem = BaseActionItem & {
    callback: () => void;
    href?: never;
};

export type ActionItem = CallbackActionItem | HrefActionItem;
