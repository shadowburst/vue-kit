<script setup lang="ts">
import { InertiaLink } from '@/components/ui/custom/link';
import { SmartMenuItem } from '@/components/ui/custom/smart-menu';
import { CapitalizeText } from '@/components/ui/custom/typography';
import { toValue } from 'vue';
import type { ActionItem } from './interface';

type Props = {
    action: ActionItem;
};
const { action } = defineProps<Props>();
</script>

<template>
    <SmartMenuItem v-if="action.href" as-child>
        <InertiaLink :href="toValue(action.href)" :disabled="toValue(action.disabled)">
            <component :is="action.icon" />
            <CapitalizeText>
                {{ action.label }}
            </CapitalizeText>
        </InertiaLink>
    </SmartMenuItem>
    <SmartMenuItem v-else-if="action.callback" :disabled="toValue(action.disabled)" @click="action.callback()">
        <component :is="action.icon" />
        <CapitalizeText>
            {{ action.label }}
        </CapitalizeText>
    </SmartMenuItem>
</template>
