<script setup lang="ts">
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Field, FieldControl, FieldError, FieldGroup, FieldLabel, Form } from '@/components/ui/custom/form';
import { Input } from '@/components/ui/input';
import { useFormat } from '@/composables/useFormat';
import type { ProfileEditProps, UserResource } from '@/spatie/types';
import ProfileController from '@/wayfinder/App/Http/Controllers/Settings/ProfileController';
import EmailVerificationNotificationController from '@/wayfinder/Laravel/Fortify/Http/Controllers/EmailVerificationNotificationController';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<ProfileEditProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: ProfileController.edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user as UserResource);
const { formatDate } = useFormat();

const form = useForm({
    name: user.value.name,
    email: user.value.email,
});
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="flex flex-col space-y-6">
        <Heading variant="small" title="Profile information" description="Update your name and email address" />

        <Form :form="form" :action="ProfileController.update()" class="space-y-6">
            <FieldGroup>
                <Field required>
                    <FieldLabel>Name</FieldLabel>
                    <FieldControl>
                        <Input v-model="form.name" name="name" autocomplete="name" placeholder="Full name" />
                    </FieldControl>
                    <FieldError :errors="[form.errors.name]" />
                </Field>

                <Field required>
                    <FieldLabel>Email address</FieldLabel>
                    <FieldControl>
                        <Input
                            v-model="form.email"
                            type="email"
                            name="email"
                            autocomplete="username"
                            placeholder="Email address"
                        />
                    </FieldControl>
                    <FieldError :errors="[form.errors.email]" />
                </Field>

                <div v-if="mustVerifyEmail && !user.email_verified_at">
                    <p class="-mt-4 text-sm text-muted-foreground">
                        Your email address is unverified.
                        <Link
                            :href="EmailVerificationNotificationController.store()"
                            as="button"
                            class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                        >
                            Click here to resend the verification email.
                        </Link>
                    </p>

                    <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                        A new verification link has been sent to your email address.
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="form.processing" data-test="update-profile-button">Save</Button>
                </div>
            </FieldGroup>
        </Form>

        <p v-if="user.created_at" class="text-sm text-muted-foreground">
            Member since {{ formatDate(user.created_at) }}
        </p>
    </div>

    <DeleteUser />
</template>
