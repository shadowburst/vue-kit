<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Form } from '@/components/ui/custom/form';
import { InertiaLink } from '@/components/ui/custom/inertia-link';
import { Spinner } from '@/components/ui/spinner';
import AuthenticatedSessionController from '@/wayfinder/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import EmailVerificationNotificationController from '@/wayfinder/Laravel/Fortify/Http/Controllers/EmailVerificationNotificationController';
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';

setLayoutProps({
    title: trans('auth.verify_email.layout_title'),
    description: trans('auth.verify_email.description'),
});

defineProps<{
    status?: string;
}>();

const form = useForm({});
</script>

<template>
    <Head :title="trans('auth.verify_email.title')" />

    <div v-if="status === 'verification-link-sent'" class="mb-4 text-center text-sm font-medium text-green-600">
        {{ trans('auth.verify_email.sent') }}
    </div>

    <Form :form="form" :action="EmailVerificationNotificationController.store()" class="space-y-6 text-center">
        <Button :disabled="form.processing" variant="secondary">
            <Spinner v-if="form.processing" />
            {{ trans('auth.verify_email.resend') }}
        </Button>

        <InertiaLink
            variant="text"
            :href="AuthenticatedSessionController.destroy()"
            as="button"
            class="mx-auto block text-sm"
        >
            {{ trans('common.log_out') }}
        </InertiaLink>
    </Form>
</template>
