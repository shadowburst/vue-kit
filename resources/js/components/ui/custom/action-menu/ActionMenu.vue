<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { SmartMenu, SmartMenuContent, SmartMenuTrigger } from '@/components/ui/custom/smart-menu';
import { cn } from '@/lib/utils';
import { EllipsisVerticalIcon } from '@lucide/vue';
import type { DropdownMenuContentProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import ActionMenuButton from './ActionMenuButton.vue';
import ActionMenuDropdownItem from './ActionMenuDropdownItem.vue';
import type { ActionItem } from './interface';

type Props = {
    actions: ActionItem[];
    buttons?: number | boolean;
    align?: DropdownMenuContentProps['align'];
    class?: HTMLAttributes['class'];
};
const props = withDefaults(defineProps<Props>(), {
    buttons: 0,
    align: 'end',
});

const dropdownActions = computed((): ActionItem[] => {
    if (props.buttons === true) {
        return [];
    }

    if (props.buttons === false) {
        return props.actions;
    }

    return props.actions.slice(props.buttons);
});
const buttonActions = computed(() => props.actions.slice(0, props.actions.length - dropdownActions.value.length));
</script>

<template>
    <div :class="cn('flex items-center gap-2', props.class)">
        <ActionMenuButton v-for="(action, index) in buttonActions" :key="index" :action :class="action.class" />
        <SmartMenu v-if="dropdownActions.length > 0">
            <SmartMenuTrigger as-child>
                <slot>
                    <Button variant="outline" size="icon-sm">
                        <EllipsisVerticalIcon />
                    </Button>
                </slot>
            </SmartMenuTrigger>
            <SmartMenuContent :align>
                <ActionMenuDropdownItem v-for="(action, index) in dropdownActions" :key="index" :action />
            </SmartMenuContent>
        </SmartMenu>
    </div>
</template>
