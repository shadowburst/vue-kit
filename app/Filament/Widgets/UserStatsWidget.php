<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UserStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        /** @var array{total: int, totalSevenDaysAgo: int, verified: int} $data */
        $data = Cache::remember(static::class, 60, function (): array {
            $total             = DB::table('users')->count();
            $totalSevenDaysAgo = DB::table('users')->where('created_at', '<', now()->subDays(7))->count();
            $verified          = DB::table('users')->whereNotNull('email_verified_at')->count();

            return compact('total', 'totalSevenDaysAgo', 'verified');
        });

        $newLast7Days = $data['total'] - $data['totalSevenDaysAgo'];
        $verifiedRate = $data['total'] > 0
            ? round(($data['verified'] / $data['total']) * 100, 1)
            : 0.0;

        return [
            Stat::make('Total Users', number_format($data['total']))
                ->description("+{$newLast7Days} vs 7 days ago")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->icon('heroicon-o-users'),

            Stat::make('Verified Rate', "{$verifiedRate}%")
                ->description("{$data['verified']} of {$data['total']} users verified")
                ->chart([$verifiedRate, max(0, 100 - $verifiedRate)])
                ->color('success')
                ->icon('heroicon-o-check-badge'),
        ];
    }
}
