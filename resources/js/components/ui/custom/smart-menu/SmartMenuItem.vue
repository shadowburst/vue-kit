<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { ButtonProps } from '@/components/ui/button/interface';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { useTailwindBreakpoints } from '@/composables';
import { cn } from '@/lib/utils';
import type { DropdownMenuItemProps } from 'reka-ui';
import { useForwardProps } from 'reka-ui';

type Props = DropdownMenuItemProps & ButtonProps;
const props = defineProps<Props>();

const forwardedDropdownMenuProps = useForwardProps(props as DropdownMenuItemProps);
const forwardedButtonProps = useForwardProps(props as ButtonProps);

const { md } = useTailwindBreakpoints();
</script>

<template>
    <DropdownMenuItem v-if="md" v-bind="forwardedDropdownMenuProps" :class="cn('w-full', props.class)">
        <slot />
    </DropdownMenuItem>
    <Button
        v-else
        variant="ghost"
        size="lg"
        v-bind="forwardedButtonProps"
        :class="cn('w-full justify-start', props.class)"
    >
        <slot />
    </Button>
</template>
