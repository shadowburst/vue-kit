<script setup lang="ts">
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldControl, FieldError, FieldGroup, FieldLabel, Form } from '@/components/ui/custom/form';
import { InertiaLink } from '@/components/ui/custom/inertia-link';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthenticatedSessionController from '@/wayfinder/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import PasswordResetLinkController from '@/wayfinder/Laravel/Fortify/Http/Controllers/PasswordResetLinkController';
import RegisteredUserController from '@/wayfinder/Laravel/Fortify/Http/Controllers/RegisteredUserController';
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

setLayoutProps({
    title: trans('auth.login.layout_title'),
    description: trans('auth.login.description'),
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false as boolean,
});
</script>

<template>
    <Head :title="trans('auth.login.title')" />

    <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
        {{ status }}
    </div>

    <Form
        :form="form"
        :action="AuthenticatedSessionController.store()"
        :options="{ onSuccess: () => form.reset('password') }"
        class="flex flex-col gap-6"
    >
        <FieldGroup>
            <Field required>
                <FieldLabel>{{ trans('auth.attributes.email') }}</FieldLabel>
                <FieldControl>
                    <Input
                        v-model="form.email"
                        type="email"
                        name="email"
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        :placeholder="trans('auth.placeholders.email')"
                    />
                </FieldControl>
                <FieldError :errors="[form.errors.email]" />
            </Field>

            <Field required>
                <div class="flex items-center justify-between">
                    <FieldLabel>{{ trans('auth.attributes.password') }}</FieldLabel>
                    <InertiaLink
                        v-if="canResetPassword"
                        variant="text"
                        :href="PasswordResetLinkController.create()"
                        class="text-sm"
                        :tabindex="5"
                    >
                        {{ trans('auth.login.forgot_password') }}
                    </InertiaLink>
                </div>
                <FieldControl>
                    <PasswordInput
                        v-model="form.password"
                        name="password"
                        :tabindex="2"
                        autocomplete="current-password"
                        :placeholder="trans('auth.attributes.password')"
                    />
                </FieldControl>
                <FieldError :errors="[form.errors.password]" />
            </Field>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" v-model="form.remember" name="remember" :tabindex="3" />
                    <span>{{ trans('auth.login.remember_me') }}</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :tabindex="4"
                :disabled="form.processing"
                data-test="login-button"
            >
                <Spinner v-if="form.processing" />
                {{ trans('common.log_in') }}
            </Button>
        </FieldGroup>

        <div class="text-center text-sm text-muted-foreground" v-if="canRegister">
            {{ trans('auth.login.no_account') }}
            <InertiaLink variant="text" :href="RegisteredUserController.create()" :tabindex="5">
                {{ trans('auth.login.sign_up') }}
            </InertiaLink>
        </div>
    </Form>
</template>
