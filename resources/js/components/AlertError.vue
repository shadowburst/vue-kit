<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertCircle } from '@lucide/vue';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

type Props = {
    errors: string[];
    title?: string;
};

const props = defineProps<Props>();

const uniqueErrors = computed(() => Array.from(new Set(props.errors)));
const titleText = computed(() => props.title ?? trans('components.alert_error.title'));
</script>

<template>
    <Alert variant="destructive">
        <AlertCircle class="size-4" />
        <AlertTitle>{{ titleText }}</AlertTitle>
        <AlertDescription>
            <ul class="list-inside list-disc text-sm">
                <li v-for="(error, index) in uniqueErrors" :key="index">
                    {{ error }}
                </li>
            </ul>
        </AlertDescription>
    </Alert>
</template>
