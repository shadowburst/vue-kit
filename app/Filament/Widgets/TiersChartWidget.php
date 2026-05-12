<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\Subscription\SubscriptionTier;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TiersChartWidget extends ChartWidget
{
    protected ?string $heading = 'Teams by Tier';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        /** @var array{freeCount: int, proCount: int} $data */
        $data = Cache::remember(static::class, 60, function (): array {
            $proCount = DB::table('teams')
                ->whereExists(
                    fn (Builder $query): Builder => $query
                        ->selectRaw('1')
                        ->from('subscriptions')
                        ->whereColumn('subscriptions.team_id', 'teams.id')
                        ->where('subscriptions.stripe_status', 'active'),
                )
                ->count();
            $totalTeams = DB::table('teams')->count();
            $freeCount  = $totalTeams - $proCount;

            return compact('freeCount', 'proCount');
        });

        return [
            'datasets' => [
                [
                    'data'            => [$data['freeCount'], $data['proCount']],
                    'backgroundColor' => ['#6B7280', '#10B981'],
                ],
            ],
            'labels'   => [
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
