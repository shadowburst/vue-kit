<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Button } from '@/components/ui/button';
import { Field, FieldControl, FieldError, FieldLabel, Form } from '@/components/ui/custom/form';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import type { SecurityEditProps } from '@/spatie/types';
import SecurityController from '@/wayfinder/App/Http/Controllers/Settings/SecurityController';
import TwoFactorAuthenticationController from '@/wayfinder/Laravel/Fortify/Http/Controllers/TwoFactorAuthenticationController';
import { Head, useForm } from '@inertiajs/vue3';
import { ShieldCheck } from '@lucide/vue';
import { onUnmounted, ref } from 'vue';

defineProps<SecurityEditProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Security settings',
                href: SecurityController.edit(),
            },
        ],
    },
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const enableTwoFactorForm = useForm({});
const disableTwoFactorForm = useForm({});

onUnmounted(() => clearTwoFactorAuthData());
</script>

<template>
    <Head title="Security settings" />

    <h1 class="sr-only">Security settings</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Update password"
            description="Ensure your account is using a long, random password to stay secure"
        />

        <Form
            :form="passwordForm"
            :action="SecurityController.update()"
            :options="{
                preserveScroll: true,
                onSuccess: () => passwordForm.reset(),
                onError: () => passwordForm.reset('password', 'password_confirmation', 'current_password'),
            }"
            class="space-y-6"
        >
            <Field>
                <FieldLabel>Current password</FieldLabel>
                <FieldControl>
                    <PasswordInput
                        v-model="passwordForm.current_password"
                        name="current_password"
                        class="mt-1 block w-full"
                        autocomplete="current-password"
                        placeholder="Current password"
                    />
                </FieldControl>
                <FieldError :errors="[passwordForm.errors.current_password]" />
            </Field>

            <Field>
                <FieldLabel>New password</FieldLabel>
                <FieldControl>
                    <PasswordInput
                        v-model="passwordForm.password"
                        name="password"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        placeholder="New password"
                    />
                </FieldControl>
                <FieldError :errors="[passwordForm.errors.password]" />
            </Field>

            <Field>
                <FieldLabel>Confirm password</FieldLabel>
                <FieldControl>
                    <PasswordInput
                        v-model="passwordForm.password_confirmation"
                        name="password_confirmation"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        placeholder="Confirm password"
                    />
                </FieldControl>
                <FieldError :errors="[passwordForm.errors.password_confirmation]" />
            </Field>

            <div class="flex items-center gap-4">
                <Button :disabled="passwordForm.processing" data-test="update-password-button"> Save password </Button>
            </div>
        </Form>
    </div>

    <div v-if="canManageTwoFactor" class="space-y-6">
        <Heading
            variant="small"
            title="Two-factor authentication"
            description="Manage your two-factor authentication settings"
        />

        <div v-if="!twoFactorEnabled" class="flex flex-col items-start justify-start space-y-4">
            <p class="text-sm text-muted-foreground">
                When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin
                can be retrieved from a TOTP-supported application on your phone.
            </p>

            <div>
                <Button v-if="hasSetupData" @click="showSetupModal = true"> <ShieldCheck />Continue setup </Button>
                <Form
                    v-else
                    :form="enableTwoFactorForm"
                    :action="TwoFactorAuthenticationController.store()"
                    :options="{ onSuccess: () => (showSetupModal = true) }"
                >
                    <Button type="submit" :disabled="enableTwoFactorForm.processing"> Enable 2FA </Button>
                </Form>
            </div>
        </div>

        <div v-else class="flex flex-col items-start justify-start space-y-4">
            <p class="text-sm text-muted-foreground">
                You will be prompted for a secure, random pin during login, which you can retrieve from the
                TOTP-supported application on your phone.
            </p>

            <div class="relative inline">
                <Form :form="disableTwoFactorForm" :action="TwoFactorAuthenticationController.destroy()">
                    <Button variant="destructive" type="submit" :disabled="disableTwoFactorForm.processing">
                        Disable 2FA
                    </Button>
                </Form>
            </div>

            <TwoFactorRecoveryCodes />
        </div>

        <TwoFactorSetupModal
            v-model:isOpen="showSetupModal"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />
    </div>
</template>
