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
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { ShieldCheck } from '@lucide/vue';
import { trans } from 'laravel-vue-i18n';
import { onUnmounted, ref } from 'vue';

defineProps<SecurityEditProps>();

setLayoutProps({
    breadcrumbs: [
        {
            title: trans('settings.security.title'),
            href: SecurityController.edit(),
        },
    ],
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
    <Head :title="trans('settings.security.title')" />

    <h1 class="sr-only">{{ trans('settings.security.title') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="trans('settings.security.update_password')"
            :description="trans('settings.security.password_description')"
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
                <FieldLabel>{{ trans('settings.attributes.current_password') }}</FieldLabel>
                <FieldControl>
                    <PasswordInput
                        v-model="passwordForm.current_password"
                        name="current_password"
                        class="mt-1 block w-full"
                        autocomplete="current-password"
                        :placeholder="trans('settings.attributes.current_password')"
                    />
                </FieldControl>
                <FieldError :errors="[passwordForm.errors.current_password]" />
            </Field>

            <Field>
                <FieldLabel>{{ trans('settings.attributes.new_password') }}</FieldLabel>
                <FieldControl>
                    <PasswordInput
                        v-model="passwordForm.password"
                        name="password"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        :placeholder="trans('settings.attributes.new_password')"
                    />
                </FieldControl>
                <FieldError :errors="[passwordForm.errors.password]" />
            </Field>

            <Field>
                <FieldLabel>{{ trans('settings.attributes.password_confirmation') }}</FieldLabel>
                <FieldControl>
                    <PasswordInput
                        v-model="passwordForm.password_confirmation"
                        name="password_confirmation"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        :placeholder="trans('settings.attributes.password_confirmation')"
                    />
                </FieldControl>
                <FieldError :errors="[passwordForm.errors.password_confirmation]" />
            </Field>

            <div class="flex items-center gap-4">
                <Button :disabled="passwordForm.processing" data-test="update-password-button">
                    {{ trans('settings.security.save_password') }}
                </Button>
            </div>
        </Form>
    </div>

    <div v-if="canManageTwoFactor" class="space-y-6">
        <Heading
            variant="small"
            :title="trans('settings.security.two_factor_title')"
            :description="trans('settings.security.two_factor_description')"
        />

        <div v-if="!twoFactorEnabled" class="flex flex-col items-start justify-start space-y-4">
            <p class="text-sm text-muted-foreground">
                {{ trans('settings.security.two_factor_disabled_body') }}
            </p>

            <div>
                <Button v-if="hasSetupData" @click="showSetupModal = true">
                    <ShieldCheck />{{ trans('settings.security.continue_setup') }}
                </Button>
                <Form
                    v-else
                    :form="enableTwoFactorForm"
                    :action="TwoFactorAuthenticationController.store()"
                    :options="{ onSuccess: () => (showSetupModal = true) }"
                >
                    <Button type="submit" :disabled="enableTwoFactorForm.processing">
                        {{ trans('settings.security.enable_two_factor') }}
                    </Button>
                </Form>
            </div>
        </div>

        <div v-else class="flex flex-col items-start justify-start space-y-4">
            <p class="text-sm text-muted-foreground">
                {{ trans('settings.security.two_factor_enabled_body') }}
            </p>

            <div class="relative inline">
                <Form :form="disableTwoFactorForm" :action="TwoFactorAuthenticationController.destroy()">
                    <Button variant="destructive" type="submit" :disabled="disableTwoFactorForm.processing">
                        {{ trans('settings.security.disable_two_factor') }}
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
