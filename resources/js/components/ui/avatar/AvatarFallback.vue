<script setup lang="ts">
import { cn } from '@/lib/utils';
import { reactiveOmit } from '@vueuse/core';
import type { AvatarFallbackProps } from 'reka-ui';
import { AvatarFallback } from 'reka-ui';
import type { HTMLAttributes } from 'vue';

const props = defineProps<AvatarFallbackProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');
</script>

<template>
    <AvatarFallback
        data-slot="avatar-fallback"
        v-bind="delegatedProps"
        :class="
            cn(
                'flex size-full items-center justify-center rounded-full bg-muted text-sm text-muted-foreground group-data-[size=sm]/avatar:text-xs',
                props.class,
            )
        "
    >
        <slot />
    </AvatarFallback>
</template>
