<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Field, FieldControl, FieldError, FieldLabel, Form } from '@/components/ui/custom/form';
import { InertiaLink } from '@/components/ui/custom/inertia-link';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import AuthenticatedSessionController from '@/wayfinder/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import PasswordResetLinkController from '@/wayfinder/Laravel/Fortify/Http/Controllers/PasswordResetLinkController';
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

setLayoutProps({
    title: trans('auth.forgot_password.title'),
    description: trans('auth.forgot_password.description'),
});

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});
</script>

<template>
    <Head :title="trans('auth.forgot_password.title')" />

    <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form :form="form" :action="PasswordResetLinkController.store()">
            <Field>
                <FieldLabel>{{ trans('auth.attributes.email') }}</FieldLabel>
                <FieldControl>
                    <Input
                        v-model="form.email"
                        type="email"
                        name="email"
                        autocomplete="off"
                        autofocus
                        :placeholder="trans('auth.placeholders.email')"
                    />
                </FieldControl>
                <FieldError :errors="[form.errors.email]" />
            </Field>

            <div class="my-6 flex items-center justify-start">
                <Button class="w-full" :disabled="form.processing" data-test="email-password-reset-link-button">
                    <Spinner v-if="form.processing" />
                    {{ trans('auth.forgot_password.submit') }}
                </Button>
            </div>
        </Form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>{{ trans('auth.forgot_password.return_to') }}</span>
            <InertiaLink variant="text" :href="AuthenticatedSessionController.create()">
                {{ trans('common.log_in') }}
            </InertiaLink>
        </div>
    </div>
</template>
