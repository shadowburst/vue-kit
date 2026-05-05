<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { route } from '@/spatie/helpers/route';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Language settings',
                href: route('locale.edit'),
            },
        ],
    },
});

const page = usePage();
const appLocales = page.props.appLocales as string[];

const form = useForm({
    locale: page.props.locale as string,
});

function localeLabel(locale: string): string {
    try {
        return new Intl.DisplayNames([locale], { type: 'language' }).of(locale) ?? locale;
    } catch {
        return locale;
    }
}

function submit(): void {
    if (page.props.auth.user) {
        form.patch(route('locale.store'));
    } else {
        form.put(route('locale.update'));
    }
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

                <Select :model-value="form.locale" @update:model-value="(val) => (form.locale = val as string)">
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
