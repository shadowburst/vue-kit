<script setup lang="ts">
import { DialogScrollContent } from '@/components/ui/dialog';
import { DrawerContent } from '@/components/ui/drawer';
import { cn } from '@/lib/utils';
import { reactiveOmit } from '@vueuse/core';
import { DialogContentProps, FocusScope, Primitive, useForwardProps } from 'reka-ui';
import { HTMLAttributes } from 'vue';
import { injectSmartModalRootContext } from './SmartModal.vue';

type Props = DialogContentProps & {
    size?: 'default' | 'lg' | 'xl';
    class?: HTMLAttributes['class'];
    overlayClass?: HTMLAttributes['class'];
};
const props = withDefaults(defineProps<Props>(), {
    size: 'default',
    as: 'div',
    overlayClass: 'z-50',
});

const forwarded = useForwardProps(reactiveOmit(props, 'as', 'asChild', 'size', 'class', 'overlayClass'));
const { isDesktop } = injectSmartModalRootContext();
</script>

<template>
    <DialogScrollContent
        v-if="isDesktop"
        v-bind="forwarded"
        :overlay-class
        :data-size="size"
        :class="
            cn(
                {
                    'w-full': size !== 'default',
                    'max-w-[min(calc(100%-var(--spacing)*4),var(--container-3xl))]': size === 'lg',
                    'max-w-[min(calc(100%-var(--spacing)*4),var(--container-8xl))]': size === 'xl',
                },
                props.class,
            )
        "
    >
        <FocusScope :tabindex="undefined" as-child>
            <Primitive :as :as-child :class="cn('flex w-full flex-1 flex-col gap-4')">
                <slot />
            </Primitive>
        </FocusScope>
    </DialogScrollContent>
    <DrawerContent
        v-else
        v-bind="forwarded"
        :data-size="size"
        :class="cn('not-data-[size=default]:h-[80svh]', props.class)"
    >
        <Primitive :as :as-child class="flex h-full flex-col gap-6 overflow-y-auto p-4">
            <slot />
        </Primitive>
    </DrawerContent>
</template>
