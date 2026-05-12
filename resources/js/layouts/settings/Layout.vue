<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';
import AppearanceController from '@/wayfinder/App/Http/Controllers/Settings/AppearanceController';
import LocaleController from '@/wayfinder/App/Http/Controllers/Settings/LocaleController';
import ProfileController from '@/wayfinder/App/Http/Controllers/Settings/ProfileController';
import SecurityController from '@/wayfinder/App/Http/Controllers/Settings/SecurityController';
import { Link } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

const sidebarNavItems: NavItem[] = [
    {
        title: trans('settings.navigation.profile'),
        href: ProfileController.edit(),
    },
    {
        title: trans('settings.navigation.security'),
        href: SecurityController.edit(),
    },
    {
        title: trans('settings.navigation.appearance'),
        href: AppearanceController.edit(),
    },
    {
        title: trans('settings.navigation.language'),
        href: LocaleController.edit(),
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading :title="trans('settings.title')" :description="trans('settings.description')" />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-1 space-x-0" :aria-label="trans('settings.title')">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="['w-full justify-start', { 'bg-muted': isCurrentOrParentUrl(item.href) }]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
