<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Field, FieldControl, FieldError, FieldLabel, Form } from '@/components/ui/custom/form';
import { InertiaLink } from '@/components/ui/custom/inertia-link';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import AuthenticatedSessionController from '@/wayfinder/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import PasswordResetLinkController from '@/wayfinder/Laravel/Fortify/Http/Controllers/PasswordResetLinkController';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({
    layout: {
        title: 'Forgot password',
        description: 'Enter your email to receive a password reset link',
    },
});

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});
</script>

<template>
    <Head title="Forgot password" />

    <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form :form="form" :action="PasswordResetLinkController.store()">
            <Field>
                <FieldLabel>Email address</FieldLabel>
                <FieldControl>
                    <Input
                        v-model="form.email"
                        type="email"
                        name="email"
                        autocomplete="off"
                        autofocus
                        placeholder="email@example.com"
                    />
                </FieldControl>
                <FieldError :errors="[form.errors.email]" />
            </Field>

            <div class="my-6 flex items-center justify-start">
                <Button class="w-full" :disabled="form.processing" data-test="email-password-reset-link-button">
                    <Spinner v-if="form.processing" />
                    Email password reset link
                </Button>
            </div>
        </Form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>Or, return to</span>
            <InertiaLink variant="text" :href="AuthenticatedSessionController.create()">log in</InertiaLink>
        </div>
    </div>
</template>
