<script setup lang="ts">
import type { TeamCreateProps } from '@/spatie/types';
import { Head } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

defineProps<TeamCreateProps>();
</script>

<template>
    <Head :title="trans('team.create_title')" />

    <div class="flex h-full flex-1 flex-col items-center justify-center p-8">
        <p class="text-muted-foreground">{{ trans('team.create_coming_soon') }}</p>
    </div>
</template>
