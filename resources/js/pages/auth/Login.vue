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
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({
    layout: {
        title: 'Log in to your account',
        description: 'Enter your email and password below to log in',
    },
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
    <Head title="Log in" />

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
                <FieldLabel>Email address</FieldLabel>
                <FieldControl>
                    <Input
                        v-model="form.email"
                        type="email"
                        name="email"
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                </FieldControl>
                <FieldError :errors="[form.errors.email]" />
            </Field>

            <Field required>
                <div class="flex items-center justify-between">
                    <FieldLabel>Password</FieldLabel>
                    <InertiaLink
                        v-if="canResetPassword"
                        variant="text"
                        :href="PasswordResetLinkController.create()"
                        class="text-sm"
                        :tabindex="5"
                    >
                        Forgot password?
                    </InertiaLink>
                </div>
                <FieldControl>
                    <PasswordInput
                        v-model="form.password"
                        name="password"
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Password"
                    />
                </FieldControl>
                <FieldError :errors="[form.errors.password]" />
            </Field>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" v-model="form.remember" name="remember" :tabindex="3" />
                    <span>Remember me</span>
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
                Log in
            </Button>
        </FieldGroup>

        <div class="text-center text-sm text-muted-foreground" v-if="canRegister">
            Don't have an account?
            <InertiaLink variant="text" :href="RegisteredUserController.create()" :tabindex="5">Sign up</InertiaLink>
        </div>
    </Form>
</template>
