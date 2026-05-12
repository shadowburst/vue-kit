<script setup lang="ts">
import { InertiaLink } from '@/components/ui/custom/inertia-link';
import { SmartMenuItem } from '@/components/ui/custom/smart-menu';
import { upperFirst } from 'es-toolkit';
import { computed, toValue } from 'vue';
import type { ActionItem } from './interface';

type Props = {
    action: ActionItem;
};
const { action } = defineProps<Props>();

const label = computed((): string => upperFirst(action.label));
</script>

<template>
    <SmartMenuItem v-if="action.href" as-child>
        <InertiaLink :href="toValue(action.href)" :disabled="toValue(action.disabled)">
            <component :is="action.icon" />
            {{ label }}
        </InertiaLink>
    </SmartMenuItem>
    <SmartMenuItem v-else-if="action.callback" :disabled="toValue(action.disabled)" @click="action.callback()">
        <component :is="action.icon" />
        {{ label }}
    </SmartMenuItem>
</template>
