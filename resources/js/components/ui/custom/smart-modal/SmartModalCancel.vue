<script setup lang="ts">
import { Button } from '@/components/ui/button';
import SmartModalClose from './SmartModalClose.vue';
</script>

<template>
    <SmartModalClose as-child>
        <Button variant="outline">
            {{ $t('cancel') }}
        </Button>
    </SmartModalClose>
</template>
