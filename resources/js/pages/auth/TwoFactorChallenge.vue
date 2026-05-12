<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { FieldError, Form } from '@/components/ui/custom/form';
import { Input } from '@/components/ui/input';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import type { TwoFactorConfigContent } from '@/types';
import TwoFactorAuthenticatedSessionController from '@/wayfinder/Laravel/Fortify/Http/Controllers/TwoFactorAuthenticatedSessionController';
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watchEffect } from 'vue';

const authConfigContent = computed<TwoFactorConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: trans('auth.two_factor_challenge.recovery_title'),
            description: trans('auth.two_factor_challenge.recovery_description'),
            buttonText: trans('auth.two_factor_challenge.use_authentication_code'),
        };
    }

    return {
        title: trans('auth.two_factor_challenge.authentication_title'),
        description: trans('auth.two_factor_challenge.authentication_description'),
        buttonText: trans('auth.two_factor_challenge.use_recovery_code'),
    };
});

watchEffect(() => {
    setLayoutProps({
        title: authConfigContent.value.title,
        description: authConfigContent.value.description,
    });
});

const showRecoveryInput = ref<boolean>(false);

const otpForm = useForm({ code: '' });
const recoveryForm = useForm({ recovery_code: '' });

const toggleRecoveryMode = (): void => {
    showRecoveryInput.value = !showRecoveryInput.value;
    otpForm.clearErrors();
    recoveryForm.clearErrors();
    otpForm.reset('code');
    recoveryForm.reset('recovery_code');
};
</script>

<template>
    <Head :title="trans('auth.two_factor_challenge.title')" />

    <div class="space-y-6">
        <template v-if="!showRecoveryInput">
            <Form
                :form="otpForm"
                :action="TwoFactorAuthenticatedSessionController.store()"
                :options="{ onError: () => otpForm.reset('code') }"
                class="space-y-4"
            >
                <div class="flex flex-col items-center justify-center space-y-3 text-center">
                    <div class="flex w-full items-center justify-center">
                        <InputOTP
                            id="otp"
                            v-model="otpForm.code"
                            :maxlength="6"
                            :disabled="otpForm.processing"
                            autofocus
                        >
                            <InputOTPGroup>
                                <InputOTPSlot v-for="index in 6" :key="index" :index="index - 1" />
                            </InputOTPGroup>
                        </InputOTP>
                    </div>
                    <FieldError :errors="[otpForm.errors.code]" />
                </div>
                <Button type="submit" class="w-full" :disabled="otpForm.processing">
                    {{ trans('common.continue') }}
                </Button>
                <div class="text-center text-sm text-muted-foreground">
                    <span>{{ trans('auth.two_factor_challenge.or_you_can') }} </span>
                    <button
                        type="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                        @click="toggleRecoveryMode"
                    >
                        {{ authConfigContent.buttonText }}
                    </button>
                </div>
            </Form>
        </template>

        <template v-else>
            <Form
                :form="recoveryForm"
                :action="TwoFactorAuthenticatedSessionController.store()"
                :options="{ onError: () => recoveryForm.reset('recovery_code') }"
                class="space-y-4"
            >
                <Input
                    v-model="recoveryForm.recovery_code"
                    name="recovery_code"
                    type="text"
                    :placeholder="trans('auth.two_factor_challenge.recovery_code_placeholder')"
                    :autofocus="showRecoveryInput"
                    required
                />
                <FieldError :errors="[recoveryForm.errors.recovery_code]" />
                <Button type="submit" class="w-full" :disabled="recoveryForm.processing">
                    {{ trans('common.continue') }}
                </Button>

                <div class="text-center text-sm text-muted-foreground">
                    <span>{{ trans('auth.two_factor_challenge.or_you_can') }} </span>
                    <button
                        type="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                        @click="toggleRecoveryMode"
                    >
                        {{ authConfigContent.buttonText }}
                    </button>
                </div>
            </Form>
        </template>
    </div>
</template>
