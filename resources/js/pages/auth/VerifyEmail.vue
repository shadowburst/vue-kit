<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Form } from '@/components/ui/custom/form';
import { InertiaLink } from '@/components/ui/custom/inertia-link';
import { Spinner } from '@/components/ui/spinner';
import AuthenticatedSessionController from '@/wayfinder/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import EmailVerificationNotificationController from '@/wayfinder/Laravel/Fortify/Http/Controllers/EmailVerificationNotificationController';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({
    layout: {
        title: 'Verify email',
        description: 'Please verify your email address by clicking on the link we just emailed to you.',
    },
});

defineProps<{
    status?: string;
}>();

const form = useForm({});
</script>

<template>
    <Head title="Email verification" />

    <div v-if="status === 'verification-link-sent'" class="mb-4 text-center text-sm font-medium text-green-600">
        A new verification link has been sent to the email address you provided during registration.
    </div>

    <Form :form="form" :action="EmailVerificationNotificationController.store()" class="space-y-6 text-center">
        <Button :disabled="form.processing" variant="secondary">
            <Spinner v-if="form.processing" />
            Resend verification email
        </Button>

        <InertiaLink
            variant="text"
            :href="AuthenticatedSessionController.destroy()"
            as="button"
            class="mx-auto block text-sm"
        >
            Log out
        </InertiaLink>
    </Form>
</template>
