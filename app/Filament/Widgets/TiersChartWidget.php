<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\Subscription\SubscriptionTier;
use App\Models\Team;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class TiersChartWidget extends ChartWidget
{
    protected ?string $heading = 'Teams by Tier';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = Cache::remember(static::class, 60, function (): array {
            $proCount = Team::whereHas('subscriptions', fn ($query) => $query->where('stripe_status', 'active'))->count();
            $totalTeams = Team::count();
            $freeCount = $totalTeams - $proCount;

            return compact('freeCount', 'proCount');
        });

        return [
            'datasets' => [
                [
                    'data' => [$data['freeCount'], $data['proCount']],
                    'backgroundColor' => ['#6B7280', '#10B981'],
                ],
            ],
            'labels' => [
                SubscriptionTier::Free->name,
                SubscriptionTier::Pro->name,
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
