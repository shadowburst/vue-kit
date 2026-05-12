<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class RevenueStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $data = Cache::remember(static::class, 60, function (): array {
            $activeCount = Subscription::where('stripe_status', 'active')->count();
            $trialingCount = Subscription::whereNotNull('trial_ends_at')
                ->where('trial_ends_at', '>', now())
                ->count();
            $cancellations = Subscription::whereBetween('ends_at', [now()->subDays(30), now()])->count();
            $prevCancellations = Subscription::whereBetween('ends_at', [now()->subDays(60), now()->subDays(30)])->count();

            return compact('activeCount', 'trialingCount', 'cancellations', 'prevCancellations');
        });

        $cancellationDelta = $data['cancellations'] - $data['prevCancellations'];
        $deltaLabel = $cancellationDelta >= 0
            ? "+{$cancellationDelta} vs previous 30 days"
            : "{$cancellationDelta} vs previous 30 days";

        return [
            Stat::make('Active Subscriptions', number_format($data['activeCount']))
                ->color('success')
                ->icon('heroicon-o-credit-card'),

            Stat::make('Trialing Teams', number_format($data['trialingCount']))
                ->color('info')
                ->icon('heroicon-o-clock'),

            Stat::make('Cancellations (30d)', number_format($data['cancellations']))
                ->description($deltaLabel)
                ->descriptionIcon($cancellationDelta <= 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($cancellationDelta <= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-x-circle'),
        ];
    }
}
