<script setup lang="ts">
import { Alert, AlertAction, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import ImpersonateController from '@/wayfinder/App/Http/Controllers/Impersonation/ImpersonateController';
import { Form, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

const page = usePage();

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
            <Form :action="ImpersonateController.store().url" method="post">
                <Button type="submit" size="sm" variant="outline">
                    {{ trans('admin.impersonation.leave') }}
                </Button>
            </Form>
        </AlertAction>
    </Alert>
</template>
