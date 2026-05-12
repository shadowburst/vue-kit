<script setup lang="ts">
import type { TeamIndexProps } from '@/spatie/types';
import { Head } from '@inertiajs/vue3';

defineProps<TeamIndexProps>();
</script>

<template>
    <Head title="My Teams" />

    <div class="flex h-full flex-1 flex-col items-center justify-center p-8">
        <p class="text-muted-foreground">Teams coming soon.</p>
    </div>
</template>
