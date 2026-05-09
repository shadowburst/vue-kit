<script setup lang="ts">
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { computed } from 'vue';

const page = usePage();

const canManageTeam = computed(() => page.props.auth.abilities.team.update === true);
const membersCount = computed(() => page.props.currentTeam?.members_count ?? 0);
const cap = computed(() => page.props.auth.features.teamMemberCap);
const isOverCap = computed(() => membersCount.value > cap.value);
const subscriptionActive = computed(() => page.props.auth.subscription?.active === true);
const membersOver = computed(() => Math.max(0, membersCount.value - cap.value));
</script>

<template>
    <Alert v-if="isOverCap && canManageTeam" variant="destructive" class="rounded-none border-x-0 border-t-0">
        <AlertTitle>{{ trans('billing.over_cap_banner_title') }}</AlertTitle>
        <AlertDescription v-if="subscriptionActive">
            {{ trans_choice('billing.over_cap_banner_body_active', membersOver, { count: String(membersOver) }) }}
        </AlertDescription>
        <AlertDescription v-else>
            {{ trans('billing.over_cap_banner_body_canceled') }}
        </AlertDescription>
    </Alert>
</template>
