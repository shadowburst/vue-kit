<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import StringMaxLength from '@/wayfinder/App/Enums/Validation/StringMaxLength';
import ConfirmablePasswordController from '@/wayfinder/Laravel/Fortify/Http/Controllers/ConfirmablePasswordController';
import { Form, Head } from '@inertiajs/vue3';

defineOptions({
    layout: {
        title: 'Confirm your password',
        description: 'This is a secure area of the application. Please confirm your password before continuing.',
    },
});

defineProps<App.Data.Auth.AuthConfirmPasswordProps>();
</script>

<template>
    <Head title="Confirm password" />

    <Form :action="ConfirmablePasswordController.store()" reset-on-success v-slot="{ errors, processing }">
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label htmlFor="password">Password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                    autofocus
                    :maxlength="StringMaxLength.Short"
                />

                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center">
                <Button class="w-full" :disabled="processing" data-test="confirm-password-button">
                    <Spinner v-if="processing" />
                    Confirm password
                </Button>
            </div>
        </div>
    </Form>
</template>
