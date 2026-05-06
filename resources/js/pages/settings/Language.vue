<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Locale } from '@/wayfinder/App/Enums/Settings/Locale';
import LocaleController from '@/wayfinder/App/Http/Controllers/Settings/LocaleController';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Language settings',
                href: LocaleController.edit(),
            },
        ],
    },
});

const page = usePage();
const appLocales = Object.values(Locale);

const form = useForm({
    locale: page.props.locale,
});

function localeLabel(locale: string): string {
    try {
        return new Intl.DisplayNames([locale], { type: 'language' }).of(locale) ?? locale;
    } catch {
        return locale;
    }
}

function submit(): void {
    form.patch(LocaleController.update.url());
}
</script>

<template>
    <Head title="Language settings" />

    <h1 class="sr-only">{{ trans('settings.language') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="trans('settings.language')"
            :description="trans('settings.language_description')"
        />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="locale">{{ trans('settings.language') }}</Label>

                <Select :model-value="form.locale" @update:model-value="(val) => (form.locale = String(val))">
                    <SelectTrigger id="locale" class="w-48">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="locale in appLocales" :key="locale" :value="locale">
                            {{ localeLabel(locale) }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <InputError :message="form.errors.locale" />
            </div>

            <Button type="submit" :disabled="form.processing">
                {{ trans('settings.save') }}
            </Button>
        </form>
    </div>
</template>
