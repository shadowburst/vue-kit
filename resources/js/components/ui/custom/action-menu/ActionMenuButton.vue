<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { InertiaLink } from '@/components/ui/custom/link';
import { CapitalizeText } from '@/components/ui/custom/typography';
import { computed, HTMLAttributes, toValue } from 'vue';
import { ActionItem } from './interface';

type Props = {
    action: ActionItem;
    class?: HTMLAttributes['class'];
};
const props = withDefaults(defineProps<Props>(), {
    class: '',
});

const variant = computed((): ActionItem['variant'] => props.action.variant ?? 'outline');
const size = computed((): ActionItem['size'] => props.action.size ?? 'icon-sm');
const label = computed((): string => (!size.value?.startsWith('icon') ? props.action.label : ''));
</script>

<template>
    <Button v-if="action.href" as-child :variant :size :class="props.class">
        <InertiaLink :href="toValue(action.href)" :disabled="toValue(action.disabled)">
            <component :is="action.icon" />
            <CapitalizeText v-if="label">
                {{ label }}
            </CapitalizeText>
        </InertiaLink>
    </Button>
    <Button
        v-else-if="action.callback"
        :variant
        :size
        :disabled="toValue(action.disabled)"
        :class="props.class"
        @click="action.callback()"
    >
        <component :is="action.icon" />
        <CapitalizeText v-if="label">
            {{ label }}
        </CapitalizeText>
    </Button>
</template>
