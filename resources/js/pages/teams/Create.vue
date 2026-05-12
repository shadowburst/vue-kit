<script setup lang="ts">
import type { TeamCreateProps } from '@/spatie/types';
import { Head } from '@inertiajs/vue3';

defineProps<TeamCreateProps>();
</script>

<template>
    <Head title="Create Team" />

    <div class="flex h-full flex-1 flex-col items-center justify-center p-8">
        <p class="text-muted-foreground">Team creation coming soon.</p>
    </div>
</template>
