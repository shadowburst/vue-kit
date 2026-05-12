<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Form } from '@/components/ui/custom/form';
import BillingController from '@/wayfinder/App/Http/Controllers/Settings/Team/BillingController';
import CancelController from '@/wayfinder/App/Http/Controllers/Settings/Team/CancelController';
import CheckoutController from '@/wayfinder/App/Http/Controllers/Settings/Team/CheckoutController';
import PortalController from '@/wayfinder/App/Http/Controllers/Settings/Team/PortalController';
import ResumeController from '@/wayfinder/App/Http/Controllers/Settings/Team/ResumeController';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed, onMounted, ref, watch } from 'vue';

type Props = {
    tier: 'free' | 'pro';
    interval: 'monthly' | 'yearly' | null;
    subscriptionStatus: 'active' | 'grace' | null;
    pmLastFour: string | null;
    nextChargeDate: string | null;
    nextChargeAmount: string | null;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Billing',
                href: BillingController.show().url,
            },
        ],
    },
});

const page = usePage();

const canManageSubscription = computed(() => page.props.auth.abilities.subscription.update === true);
const canCancel = computed(() => page.props.auth.abilities.subscription.cancel === true);
const canResume = computed(() => page.props.auth.abilities.subscription.resume === true);
const membersCount = computed(() => page.props.currentTeam?.members_count ?? 0);

const selectedInterval = ref<'monthly' | 'yearly'>('monthly');

const checkoutForm = useForm({
    interval: selectedInterval.value,
});
const cancelForm = useForm({});
const resumeForm = useForm({});

watch(selectedInterval, (value) => {
    checkoutForm.interval = value;
});

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('checkout') === 'success' || params.get('portal') === 'return') {
        router.reload({ only: ['auth'] });
    }
});
</script>

<template>
    <Head :title="trans('billing.title')" />

    <h1 class="sr-only">{{ trans('billing.title') }}</h1>

    <div class="space-y-6">
        <Heading variant="small" :title="trans('billing.title')" :description="trans('billing.description')" />

        <div v-if="tier === 'free'" class="space-y-4">
            <div class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground">{{ trans('billing.current_tier') }}</span>
                <Badge variant="secondary">{{ trans('billing.tier_free') }}</Badge>
            </div>

            <div class="flex w-fit items-center gap-1 rounded-lg border p-1">
                <button
                    type="button"
                    :class="[
                        'rounded px-3 py-1.5 text-sm font-medium transition-all',
                        selectedInterval === 'monthly'
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                    @click="selectedInterval = 'monthly'"
                >
                    {{ trans('billing.interval_monthly') }}
                </button>

                <button
                    type="button"
                    :class="[
                        'rounded px-3 py-1.5 text-sm font-medium transition-all',
                        selectedInterval === 'yearly'
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground',
                    ]"
                    @click="selectedInterval = 'yearly'"
                >
                    {{ trans('billing.interval_yearly') }}
                </button>
            </div>

            <Form :form="checkoutForm" :action="CheckoutController.store()">
                <Button type="submit" :disabled="checkoutForm.processing">
                    {{ trans('billing.upgrade_to_pro') }}
                </Button>
            </Form>
        </div>

        <div v-else-if="tier === 'pro'" class="space-y-4">
            <div class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground">{{ trans('billing.current_tier') }}</span>
                <Badge>{{ trans('billing.tier_pro') }}</Badge>
                <Badge v-if="subscriptionStatus === 'grace'" variant="destructive">
                    {{ trans('billing.status_grace') }}
                </Badge>
            </div>

            <div v-if="interval" class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground">{{ trans('billing.billing_interval') }}</span>
                <span class="text-sm">{{
                    interval === 'monthly' ? trans('billing.interval_monthly') : trans('billing.interval_yearly')
                }}</span>
            </div>

            <div v-if="nextChargeDate" class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground">{{ trans('billing.next_charge') }}</span>
                <span class="text-sm"
                    >{{ nextChargeDate }}<template v-if="nextChargeAmount"> ({{ nextChargeAmount }})</template></span
                >
            </div>

            <div v-if="pmLastFour" class="flex items-center gap-2">
                <span class="text-sm text-muted-foreground">{{ trans('billing.payment_method') }}</span>
                <span class="text-sm">&bull;&bull;&bull;&bull; {{ pmLastFour }}</span>
            </div>

            <template v-if="canManageSubscription">
                <Form
                    v-if="subscriptionStatus === 'active'"
                    :form="cancelForm"
                    :action="CancelController.store()"
                    :can-submit="canCancel"
                >
                    <Button type="submit" variant="destructive" :disabled="!canCancel || cancelForm.processing">
                        {{
                            canCancel
                                ? trans('billing.cancel_subscription')
                                : trans_choice('billing.cancel_over_cap', membersCount, { count: String(membersCount) })
                        }}
                    </Button>
                </Form>

                <Form
                    v-else-if="subscriptionStatus === 'grace' && canResume"
                    :form="resumeForm"
                    :action="ResumeController.store()"
                >
                    <Button type="submit" :disabled="resumeForm.processing">
                        {{ trans('billing.resume_subscription') }}
                    </Button>
                </Form>
            </template>

            <Button v-if="canManageSubscription" as="a" :href="PortalController.show().url">
                {{ trans('billing.manage_billing') }}
            </Button>
        </div>
    </div>
</template>
