<script setup lang="ts">
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Field, FieldControl, FieldError, FieldGroup, FieldLabel, Form } from '@/components/ui/custom/form';
import { InertiaLink } from '@/components/ui/custom/inertia-link';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import AuthenticatedSessionController from '@/wayfinder/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import RegisteredUserController from '@/wayfinder/Laravel/Fortify/Http/Controllers/RegisteredUserController';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
</script>

<template>
    <Head title="Register" />

    <Form
        :form="form"
        :action="RegisteredUserController.store()"
        :options="{ onSuccess: () => form.reset('password', 'password_confirmation') }"
        class="flex flex-col gap-6"
    >
        <FieldGroup>
            <Field required>
                <FieldLabel>Name</FieldLabel>
                <FieldControl>
                    <Input
                        v-model="form.name"
                        type="text"
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        placeholder="Full name"
                    />
                </FieldControl>
                <FieldError :errors="[form.errors.name]" />
            </Field>

            <Field required>
                <FieldLabel>Email address</FieldLabel>
                <FieldControl>
                    <Input
                        v-model="form.email"
                        type="email"
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="email@example.com"
                    />
                </FieldControl>
                <FieldError :errors="[form.errors.email]" />
            </Field>

            <Field required>
                <FieldLabel>Password</FieldLabel>
                <FieldControl>
                    <PasswordInput
                        v-model="form.password"
                        :tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        placeholder="Password"
                    />
                </FieldControl>
                <FieldError :errors="[form.errors.password]" />
            </Field>

            <Field required>
                <FieldLabel>Confirm password</FieldLabel>
                <FieldControl>
                    <PasswordInput
                        v-model="form.password_confirmation"
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Confirm password"
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
                Create account
            </Button>
        </FieldGroup>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <InertiaLink variant="text" :href="AuthenticatedSessionController.create()" :tabindex="6"
                >Log in</InertiaLink
            >
        </div>
    </Form>
</template>
