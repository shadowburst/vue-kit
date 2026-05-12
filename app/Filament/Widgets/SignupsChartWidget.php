<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SignupsChartWidget extends ChartWidget
{
    protected ?string $heading = 'User Signups';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        /** @var array{labels: list<string>, counts: list<int>} $data */
        $data = Cache::remember(
            static::class,
            60,
            fn (): array => [
                'labels' => ['Last 7 days', 'Last 30 days'],
                'counts' => [
                    DB::table('users')->where('created_at', '>=', now()->subDays(7))->count(),
                    DB::table('users')->where('created_at', '>=', now()->subDays(30))->count(),
                ],
            ],
        );

        return [
            'datasets' => [
                [
                    'label'           => 'Signups',
                    'data'            => $data['counts'],
                    'borderColor'     => '#6366F1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                ],
            ],
            'labels'   => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
