<script setup lang="ts">
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Field, FieldControl, FieldError, FieldGroup, FieldLabel, Form } from '@/components/ui/custom/form';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import NewPasswordController from '@/wayfinder/Laravel/Fortify/Http/Controllers/NewPasswordController';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({
    layout: {
        title: 'Reset password',
        description: 'Please enter your new password below',
    },
});

const props = defineProps<{
    token: string;
    email: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});
</script>

<template>
    <Head title="Reset password" />

    <Form
        :form="form"
        :action="NewPasswordController.store()"
        :options="{ onSuccess: () => form.reset('password', 'password_confirmation') }"
    >
        <FieldGroup>
            <Field>
                <FieldLabel>Email</FieldLabel>
                <FieldControl>
                    <Input
                        v-model="form.email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        class="mt-1 block w-full"
                        readonly
                    />
                </FieldControl>
                <FieldError :errors="[form.errors.email]" />
            </Field>

            <Field>
                <FieldLabel>Password</FieldLabel>
                <FieldControl>
                    <PasswordInput
                        v-model="form.password"
                        name="password"
                        autocomplete="new-password"
                        class="mt-1 block w-full"
                        autofocus
                        placeholder="Password"
                    />
                </FieldControl>
                <FieldError :errors="[form.errors.password]" />
            </Field>

            <Field>
                <FieldLabel>Confirm password</FieldLabel>
                <FieldControl>
                    <PasswordInput
                        v-model="form.password_confirmation"
                        name="password_confirmation"
                        autocomplete="new-password"
                        class="mt-1 block w-full"
                        placeholder="Confirm password"
                    />
                </FieldControl>
                <FieldError :errors="[form.errors.password_confirmation]" />
            </Field>

            <Button type="submit" class="mt-4 w-full" :disabled="form.processing" data-test="reset-password-button">
                <Spinner v-if="form.processing" />
                Reset password
            </Button>
        </FieldGroup>
    </Form>
</template>
