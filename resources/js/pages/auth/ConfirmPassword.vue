<script setup lang="ts">
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Field, FieldControl, FieldError, FieldLabel, Form } from '@/components/ui/custom/form';
import { Spinner } from '@/components/ui/spinner';
import ConfirmablePasswordController from '@/wayfinder/Laravel/Fortify/Http/Controllers/ConfirmablePasswordController';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({
    layout: {
        title: 'Confirm your password',
        description: 'This is a secure area of the application. Please confirm your password before continuing.',
    },
});

const form = useForm({
    password: '',
});
</script>

<template>
    <Head title="Confirm password" />

    <Form
        :form="form"
        :action="ConfirmablePasswordController.store()"
        :options="{ onSuccess: () => form.reset('password') }"
    >
        <div class="space-y-6">
            <Field required>
                <FieldLabel>Password</FieldLabel>
                <FieldControl>
                    <PasswordInput
                        v-model="form.password"
                        name="password"
                        class="mt-1 block w-full"
                        autocomplete="current-password"
                        autofocus
                    />
                </FieldControl>
                <FieldError :errors="[form.errors.password]" />
            </Field>

            <div class="flex items-center">
                <Button class="w-full" :disabled="form.processing" data-test="confirm-password-button">
                    <Spinner v-if="form.processing" />
                    Confirm password
                </Button>
            </div>
        </div>
    </Form>
</template>
