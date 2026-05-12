<script setup lang="ts">
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import type { DashboardIndexProps } from '@/spatie/types';
import DashboardController from '@/wayfinder/App/Http/Controllers/Dashboard/DashboardController';
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

defineProps<DashboardIndexProps>();

setLayoutProps({
    breadcrumbs: [
        {
            title: trans('common.dashboard'),
            href: DashboardController.index(),
        },
    ],
});
</script>

<template>
    <Head :title="trans('common.dashboard')" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <PlaceholderPattern />
            </div>
        </div>
        <div
            class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
        >
            <PlaceholderPattern />
        </div>
    </div>
</template>
