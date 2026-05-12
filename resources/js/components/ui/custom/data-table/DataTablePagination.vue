<script setup lang="ts" generic="TData">
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ChevronLeftIcon, ChevronRightIcon, ChevronsLeftIcon, ChevronsRightIcon } from '@lucide/vue';
import { trans } from 'laravel-vue-i18n';
import type { AcceptableValue } from 'reka-ui';
import { injectDataTableRootContext } from './DataTable.vue';

const { table } = injectDataTableRootContext<TData>();

function setPageSize(value: AcceptableValue): void {
    if (value === null) {
        return;
    }

    table.value.setPageSize(Number(value));
}
</script>

<template>
    <div class="flex gap-4">
        <div class="flex-1 pl-2 text-sm text-muted-foreground">
            {{
                trans('components.ui.custom.data_table.selected', {
                    selected: table.getFilteredSelectedRowModel().rows.length.toString(),
                    rows: table.getFilteredRowModel().rows.length.toString(),
                })
            }}
        </div>
        <div class="ml-auto flex flex-wrap justify-end gap-y-2 max-sm:w-min">
            <div class="flex items-center gap-x-2">
                <span class="text-sm font-medium capitalize">
                    {{ trans('components.ui.custom.data_table.rows_per_page') }}
                </span>
                <Select :model-value="table.getState().pagination.pageSize" @update:model-value="setPageSize">
                    <SelectTrigger class="w-[80px]">
                        <SelectValue :placeholder="`${table.getState().pagination.pageSize}`" />
                    </SelectTrigger>
                    <SelectContent side="top">
                        <SelectItem v-for="value in [15, 50, 100, 200]" :key="value" :value>
                            {{ value }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="flex items-center gap-x-2">
                <div class="flex w-[100px] items-center justify-center text-sm font-medium">
                    <span class="capitalize">
                        {{
                            trans('components.ui.custom.data_table.pages', {
                                current: (table.getState().pagination.pageIndex + 1).toString(),
                                total: table.getPageCount().toString(),
                            })
                        }}
                    </span>
                </div>
                <Button
                    class="max-lg:hidden"
                    size="icon"
                    variant="outline"
                    :disabled="!table.getCanPreviousPage()"
                    @click="table.setPageIndex(0)"
                >
                    <span class="sr-only">Go to first page</span>
                    <ChevronsLeftIcon />
                </Button>
                <Button
                    size="icon"
                    variant="outline"
                    :disabled="!table.getCanPreviousPage()"
                    @click="table.previousPage()"
                >
                    <span class="sr-only">Go to previous page</span>
                    <ChevronLeftIcon />
                </Button>
                <Button size="icon" variant="outline" :disabled="!table.getCanNextPage()" @click="table.nextPage()">
                    <span class="sr-only">Go to next page</span>
                    <ChevronRightIcon />
                </Button>
                <Button
                    class="max-lg:hidden"
                    size="icon"
                    variant="outline"
                    :disabled="!table.getCanNextPage()"
                    @click="table.setPageIndex(table.getPageCount() - 1)"
                >
                    <span class="sr-only">Go to last page</span>
                    <ChevronsRightIcon />
                </Button>
            </div>
        </div>
    </div>
</template>
