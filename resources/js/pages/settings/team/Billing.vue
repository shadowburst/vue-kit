<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { route } from '@/spatie/helpers/route';
import { Form, Head, router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { onMounted, ref } from 'vue';

type Props = {
    tier: 'free' | 'pro';
    interval: 'monthly' | 'yearly' | null;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Billing',
                href: route('teams.billing.show'),
            },
        ],
    },
});

const selectedInterval = ref<'monthly' | 'yearly'>('monthly');

onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('checkout') === 'success') {
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

            <div class="flex items-center gap-1 rounded-lg border p-1 w-fit">
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

            <Form :action="route('teams.checkout.store')" method="post">
                <input type="hidden" name="interval" :value="selectedInterval" />
                <Button type="submit">{{ trans('billing.upgrade_to_pro') }}</Button>
            </Form>
        </div>
    </div>
</template>
