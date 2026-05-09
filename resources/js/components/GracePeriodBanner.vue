<script setup lang="ts">
import { Alert, AlertAction, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import ResumeController from '@/wayfinder/App/Http/Controllers/Settings/Team/ResumeController';
import { Form, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

type GracePeriod = {
    ends_at: string;
    at_risk_count: number;
};

const page = usePage<{
    auth: {
        subscription: { grace_period: GracePeriod } | null;
        abilities: { subscription: { update: boolean } } | null;
    } | null;
}>();

const gracePeriod = computed(() => page.props.auth?.subscription?.grace_period ?? null);
const canResume = computed(() => page.props.auth?.abilities?.subscription?.update === true);

const daysLeft = computed((): number => {
    if (!gracePeriod.value) {
        return 0;
    }

    const endsAt = new Date(gracePeriod.value.ends_at);
    const now = new Date();

    return Math.max(1, Math.ceil((endsAt.getTime() - now.getTime()) / 86_400_000));
});
</script>

<template>
    <Alert v-if="gracePeriod" variant="destructive" class="rounded-none border-x-0 border-t-0">
        <AlertTitle>{{ trans('billing.grace_period_banner_title') }}</AlertTitle>
        <AlertDescription>
            {{
                trans('billing.grace_period_banner_body', {
                    count: String(gracePeriod.at_risk_count),
                    days: String(daysLeft),
                })
            }}
        </AlertDescription>
        <AlertAction v-if="canResume">
            <Form :action="ResumeController.store().url" method="post">
                <Button type="submit" size="sm" variant="outline">
                    {{ trans('billing.resume_subscription') }}
                </Button>
            </Form>
        </AlertAction>
    </Alert>
</template>
