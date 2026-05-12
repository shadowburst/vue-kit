<script setup lang="ts">
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Field, FieldControl, FieldError, FieldGroup, FieldLabel, Form } from '@/components/ui/custom/form';
import { InertiaLink } from '@/components/ui/custom/inertia-link';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import AuthenticatedSessionController from '@/wayfinder/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import RegisteredUserController from '@/wayfinder/Laravel/Fortify/Http/Controllers/RegisteredUserController';
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

setLayoutProps({
    title: trans('auth.register.layout_title'),
    description: trans('auth.register.description'),
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
</script>

<template>
    <Head :title="trans('auth.register.title')" />

    <Form
        :form="form"
        :action="RegisteredUserController.store()"
        :options="{ onSuccess: () => form.reset('password', 'password_confirmation') }"
        class="flex flex-col gap-6"
    >
        <FieldGroup>
            <Field required>
                <FieldLabel>{{ trans('auth.attributes.name') }}</FieldLabel>
                <FieldControl>
                    <Input
                        v-model="form.name"
                        type="text"
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        :placeholder="trans('settings.profile.full_name')"
                    />
                </FieldControl>
                <FieldError :errors="[form.errors.name]" />
            </Field>

            <Field required>
                <FieldLabel>{{ trans('auth.attributes.email') }}</FieldLabel>
                <FieldControl>
                    <Input
                        v-model="form.email"
                        type="email"
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        :placeholder="trans('auth.placeholders.email')"
                    />
                </FieldControl>
                <FieldError :errors="[form.errors.email]" />
            </Field>

            <Field required>
                <FieldLabel>{{ trans('auth.attributes.password') }}</FieldLabel>
                <FieldControl>
                    <PasswordInput
                        v-model="form.password"
                        :tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        :placeholder="trans('auth.attributes.password')"
                    />
                </FieldControl>
                <FieldError :errors="[form.errors.password]" />
            </Field>

            <Field required>
                <FieldLabel>{{ trans('auth.attributes.password_confirmation') }}</FieldLabel>
                <FieldControl>
                    <PasswordInput
                        v-model="form.password_confirmation"
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        :placeholder="trans('auth.attributes.password_confirmation')"
                    />
                </FieldControl>
                <FieldError :errors="[form.errors.password_confirmation]" />
            </Field>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="5"
                :disabled="form.processing"
                data-test="register-user-button"
            >
                <Spinner v-if="form.processing" />
                {{ trans('auth.register.submit') }}
            </Button>
        </FieldGroup>

        <div class="text-center text-sm text-muted-foreground">
            {{ trans('auth.register.already_registered') }}
            <InertiaLink variant="text" :href="AuthenticatedSessionController.create()" :tabindex="6">{{
                trans('common.log_in')
            }}</InertiaLink>
        </div>
    </Form>
</template>
