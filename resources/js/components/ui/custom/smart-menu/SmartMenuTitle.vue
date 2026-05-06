<script setup lang="ts">
import { DrawerDescription, DrawerHeader, DrawerTitle } from '@/components/ui/drawer';
import { DropdownMenuLabel } from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { reactiveOmit } from '@vueuse/core';
import { DropdownMenuLabelProps, useForwardProps, VisuallyHidden } from 'reka-ui';
import { DrawerTitleProps } from 'vaul-vue';
import { HTMLAttributes } from 'vue';
import { injectSmartMenuContext } from './SmartMenu.vue';

type Props = DropdownMenuLabelProps &
    DrawerTitleProps & {
        description?: string;
        class?: HTMLAttributes['class'];
    };
const props = defineProps<Props>();

const forwardedDropdownMenuProps = useForwardProps(props as DropdownMenuLabelProps);
const forwardedDrawerProps = useForwardProps(reactiveOmit(props, 'class') as DrawerTitleProps);

const { isDesktop } = injectSmartMenuContext();
</script>

<template>
    <DropdownMenuLabel v-if="isDesktop" v-bind="forwardedDropdownMenuProps">
        <slot />
    </DropdownMenuLabel>
    <DrawerHeader v-else v-bind="forwardedDrawerProps">
        <DrawerTitle :class="cn('text-primary text-lg font-semibold', props.class)">
            <slot />
        </DrawerTitle>
        <component :is="!description ? VisuallyHidden : 'template'">
            <DrawerDescription>
                {{ description ?? $t('components.ui.custom.smart_menu.description') }}
            </DrawerDescription>
        </component>
    </DrawerHeader>
</template>
