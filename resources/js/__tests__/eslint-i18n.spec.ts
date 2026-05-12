import type { Linter } from 'eslint';
import { ESLint } from 'eslint';
import { describe, expect, it } from 'vitest';
import eslintConfig from '../../../eslint.config.js';

describe('i18n lint restrictions', () => {
    it('blocks Vue i18n instance helpers in view code', async () => {
        const eslint = new ESLint({
            overrideConfig: eslintConfig as Linter.Config[],
            overrideConfigFile: true,
        });

        const [result] = await eslint.lintText(
            `<template>
                <p>{{ $t('common.save') }}</p>
                <p>{{ $tChoice('billing.cancel_over_cap', 2) }}</p>
            </template>
            <script setup lang="ts">
            void $t('common.save');
            void $tChoice('billing.cancel_over_cap', 2);
            </script>`,
            { filePath: 'resources/js/pages/Example.vue' },
        );

        expect(result.messages.map((message) => message.message)).toEqual([
            'Use trans() from laravel-vue-i18n instead of $t().',
            'Use trans_choice() from laravel-vue-i18n instead of $tChoice().',
            'Use trans() from laravel-vue-i18n instead of $t().',
            'Use trans_choice() from laravel-vue-i18n instead of $tChoice().',
        ]);
    });
});
