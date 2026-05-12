<script setup lang="ts">
import { Alert, AlertAction, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Form } from '@/components/ui/custom/form';
import ImpersonateController from '@/wayfinder/App/Http/Controllers/Impersonation/ImpersonateController';
import { useForm, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

const page = usePage();
const leaveImpersonationForm = useForm({});

const impersonator = computed(() => page.props.auth.impersonator ?? null);
const targetName = computed(() => page.props.auth.user?.name ?? '');
</script>

<template>
    <Alert v-if="impersonator" variant="destructive" class="rounded-none border-x-0 border-t-0">
        <AlertTitle>{{ trans('admin.impersonation.banner_title', { name: targetName }) }}</AlertTitle>
        <AlertDescription>
            {{ trans('admin.impersonation.banner_body', { name: targetName }) }}
        </AlertDescription>
        <AlertAction>
            <Form :form="leaveImpersonationForm" :action="ImpersonateController.store()">
                <Button type="submit" size="sm" variant="outline" :disabled="leaveImpersonationForm.processing">
                    {{ trans('admin.impersonation.leave') }}
                </Button>
            </Form>
        </AlertAction>
    </Alert>
</template>
