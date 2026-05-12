<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { InertiaLink } from '@/components/ui/custom/inertia-link';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { AuthLoginProps } from '@/spatie/types';
import StringMaxLength from '@/wayfinder/App/Enums/Validation/StringMaxLength';
import AuthenticatedSessionController from '@/wayfinder/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import PasswordResetLinkController from '@/wayfinder/Laravel/Fortify/Http/Controllers/PasswordResetLinkController';
import RegisteredUserController from '@/wayfinder/Laravel/Fortify/Http/Controllers/RegisteredUserController';
import { Form, Head } from '@inertiajs/vue3';

defineOptions({
    layout: {
        title: 'Log in to your account',
        description: 'Enter your email and password below to log in',
    },
});

defineProps<AuthLoginProps>();
</script>

<template>
    <Head title="Log in" />

    <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
        {{ status }}
    </div>

    <Form
        :action="AuthenticatedSessionController.store()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    :maxlength="StringMaxLength.Medium"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Password</Label>
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
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    :maxlength="StringMaxLength.Short"
                    placeholder="Password"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span>Remember me</span>
                </Label>
            </div>

            <Button type="submit" class="mt-4 w-full" :tabindex="4" :disabled="processing" data-test="login-button">
                <Spinner v-if="processing" />
                Log in
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground" v-if="canRegister">
            Don't have an account?
            <InertiaLink variant="text" :href="RegisteredUserController.create()" :tabindex="5">Sign up</InertiaLink>
        </div>
    </Form>
</template>
