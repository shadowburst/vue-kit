<script setup lang="ts">
import type { TeamIndexProps } from '@/spatie/types';
import { Head } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

defineProps<TeamIndexProps>();
</script>

<template>
    <Head :title="trans('team.index_title')" />

    <div class="flex h-full flex-1 flex-col items-center justify-center p-8">
        <p class="text-muted-foreground">{{ trans('team.index_coming_soon') }}</p>
    </div>
</template>
