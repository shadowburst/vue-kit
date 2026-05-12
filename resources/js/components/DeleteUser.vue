<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Field, FieldControl, FieldError, FieldLabel, Form } from '@/components/ui/custom/form';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import ProfileController from '@/wayfinder/App/Http/Controllers/Settings/ProfileController';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { useTemplateRef } from 'vue';

const passwordInput = useTemplateRef('passwordInput');

const form = useForm({
    password: '',
});
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            :title="trans('settings.delete_account.title')"
            :description="trans('settings.delete_account.description')"
        />
        <div class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">{{ trans('settings.delete_account.warning') }}</p>
                <p class="text-sm">{{ trans('settings.delete_account.warning_body') }}</p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button variant="destructive" data-test="delete-user-button">
                        {{ trans('settings.delete_account.title') }}
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        :form="form"
                        :action="ProfileController.destroy()"
                        :options="{
                            preserveScroll: true,
                            onSuccess: () => form.reset(),
                            onError: () => passwordInput?.focus(),
                        }"
                        class="space-y-6"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>{{ trans('settings.delete_account.confirm_title') }}</DialogTitle>
                            <DialogDescription>
                                {{ trans('settings.delete_account.confirm_description') }}
                            </DialogDescription>
                        </DialogHeader>

                        <Field>
                            <FieldLabel class="sr-only">{{ trans('settings.attributes.password') }}</FieldLabel>
                            <FieldControl>
                                <PasswordInput
                                    v-model="form.password"
                                    name="password"
                                    ref="passwordInput"
                                    :placeholder="trans('settings.attributes.password')"
                                />
                            </FieldControl>
                            <FieldError :errors="[form.errors.password]" />
                        </Field>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    @click="
                                        () => {
                                            form.clearErrors();
                                            form.reset();
                                        }
                                    "
                                >
                                    {{ trans('common.cancel') }}
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="form.processing"
                                data-test="confirm-delete-user-button"
                            >
                                {{ trans('settings.delete_account.title') }}
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
