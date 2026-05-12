<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { trans } from 'laravel-vue-i18n';
import SmartDialogClose from './SmartDialogClose.vue';
</script>

<template>
    <SmartDialogClose as-child>
        <Button variant="outline">
            {{ trans('common.cancel') }}
        </Button>
    </SmartDialogClose>
</template>
