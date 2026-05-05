import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export type UseFormatReturn = {
    formatDate: (value: Date | string | number, opts?: Intl.DateTimeFormatOptions) => string;
    formatNumber: (value: number, opts?: Intl.NumberFormatOptions) => string;
    formatCurrency: (value: number, currency: string, opts?: Intl.NumberFormatOptions) => string;
};

export function useFormat(): UseFormatReturn {
    const page = usePage();
    const locale = computed(() => page.props.locale as string);

    function formatDate(value: Date | string | number, opts?: Intl.DateTimeFormatOptions): string {
        return new Intl.DateTimeFormat(locale.value, opts).format(new Date(value));
    }

    function formatNumber(value: number, opts?: Intl.NumberFormatOptions): string {
        return new Intl.NumberFormat(locale.value, opts).format(value);
    }

    function formatCurrency(value: number, currency: string, opts?: Intl.NumberFormatOptions): string {
        return new Intl.NumberFormat(locale.value, { style: 'currency', currency, ...opts }).format(value);
    }

    return {
        formatDate,
        formatNumber,
        formatCurrency,
    };
}
