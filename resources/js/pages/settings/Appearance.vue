<script setup lang="ts">
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import type { AppearanceEditProps } from '@/spatie/types';
import AppearanceController from '@/wayfinder/App/Http/Controllers/Settings/AppearanceController';
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

defineProps<AppearanceEditProps>();

setLayoutProps({
    breadcrumbs: [
        {
            title: trans('settings.appearance.title'),
            href: AppearanceController.edit(),
        },
    ],
});
</script>

<template>
    <Head :title="trans('settings.appearance.title')" />

    <h1 class="sr-only">{{ trans('settings.appearance.title') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="trans('settings.appearance.title')"
            :description="trans('settings.appearance.description')"
        />
        <AppearanceTabs />
    </div>
</template>
