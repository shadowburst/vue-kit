<script setup lang="ts">
import { AlertModal } from '@/components/ui/custom/alert-modal';
import { Dialog } from '@/components/ui/dialog';
import { Drawer } from '@/components/ui/drawer';
import { useTailwindBreakpoints } from '@/composables';
import { HeadlessModal } from '@inertiaui/modal-vue';
import { provideSmartModalRootContext } from './SmartModal.vue';

const { sm: isDesktop } = useTailwindBreakpoints();

provideSmartModalRootContext({
    isDesktop,
});
</script>

<template>
    <HeadlessModal v-slot="{ isOpen, close }">
        <AlertModal>
            <Dialog v-if="isDesktop" :open="isOpen" @update:open="(open) => !open && close()">
                <slot />
            </Dialog>
            <Drawer v-else :open="isOpen" @update:open="(open) => !open && close()">
                <slot />
            </Drawer>
        </AlertModal>
    </HeadlessModal>
</template>
