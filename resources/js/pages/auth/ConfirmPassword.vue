<script setup lang="ts">
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Field, FieldControl, FieldError, FieldLabel, Form } from '@/components/ui/custom/form';
import { Spinner } from '@/components/ui/spinner';
import ConfirmablePasswordController from '@/wayfinder/Laravel/Fortify/Http/Controllers/ConfirmablePasswordController';
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

setLayoutProps({
    title: trans('auth.confirm_password.layout_title'),
    description: trans('auth.confirm_password.description'),
});

const form = useForm({
    password: '',
});
</script>

<template>
    <Head :title="trans('auth.confirm_password.title')" />

    <Form
        :form="form"
        :action="ConfirmablePasswordController.store()"
        :options="{ onSuccess: () => form.reset('password') }"
    >
        <div class="space-y-6">
            <Field required>
                <FieldLabel>{{ trans('auth.attributes.password') }}</FieldLabel>
                <FieldControl>
                    <PasswordInput
                        v-model="form.password"
                        name="password"
                        class="mt-1 block w-full"
                        autocomplete="current-password"
                        autofocus
                    />
                </FieldControl>
                <FieldError :errors="[form.errors.password]" />
            </Field>

            <div class="flex items-center">
                <Button class="w-full" :disabled="form.processing" data-test="confirm-password-button">
                    <Spinner v-if="form.processing" />
                    {{ trans('auth.confirm_password.title') }}
                </Button>
            </div>
        </div>
    </Form>
</template>
