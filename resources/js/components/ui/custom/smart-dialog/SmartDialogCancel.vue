<script setup lang="ts">
import { Button } from '@/components/ui/button';
import SmartDialogClose from './SmartDialogClose.vue';
</script>

<template>
    <SmartDialogClose as-child>
        <Button variant="outline">
            {{ $t('cancel') }}
        </Button>
    </SmartDialogClose>
</template>
