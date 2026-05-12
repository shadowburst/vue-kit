<?php

declare(strict_types=1);

use App\Enums\Role\Role;
use App\Filament\Widgets\RevenueStatsWidget;
use App\Filament\Widgets\SignupsChartWidget;
use App\Filament\Widgets\TiersChartWidget;
use App\Filament\Widgets\UserStatsWidget;
use App\Models\Team;
use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use Filament\Panel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

function makeDashboardOperator(): User
{
    $admin = User::factory()->createOne();
    $team = Team::factory()->createOne(['owner_id' => $admin->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);

    return $admin;
}

/**
 * @return array<int, mixed>
 */
function callWidgetMethod(string $widgetClass, string $method): array
{
    $widget = app($widgetClass);
    $method = new ReflectionMethod($widgetClass, $method);
    $method->setAccessible(true);

    /** @var array<int, mixed> $result */
    $result = $method->invoke($widget);

    return $result;
}

function warmDashboardWidgetCache(): void
{
    Livewire::test(UserStatsWidget::class)->assertSuccessful();
    Livewire::test(RevenueStatsWidget::class)->assertSuccessful();
    Livewire::test(SignupsChartWidget::class)->assertSuccessful();
    Livewire::test(TiersChartWidget::class)->assertSuccessful();
}

it('registers the operator dashboard widgets in the admin panel', function (): void {
    $widgets = (new AdminPanelProvider(app()))
        ->panel(Panel::make())
        ->getWidgets();

    expect(array_values($widgets))->toBe([
        UserStatsWidget::class,
        RevenueStatsWidget::class,
        SignupsChartWidget::class,
        TiersChartWidget::class,
    ]);
});

it('derives free tier from teams without an active subscription', function (): void {
    Team::factory()->createOne();

    $activeTeam = Team::factory()->createOne();
    $activeTeam->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_active_tiers',
        'stripe_status' => 'active',
        'stripe_price' => 'price_pro_monthly',
    ]);

    $canceledTeam = Team::factory()->createOne();
    $canceledTeam->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_canceled_tiers',
        'stripe_status' => 'canceled',
        'stripe_price' => 'price_pro_monthly',
    ]);

    $trialingTeam = Team::factory()->createOne();
    $trialingTeam->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_trialing_tiers',
        'stripe_status' => 'trialing',
        'stripe_price' => 'price_pro_monthly',
        'trial_ends_at' => now()->addDays(7),
    ]);

    $data = callWidgetMethod(TiersChartWidget::class, 'getData');

    expect($data['labels'])->toBe(['Free', 'Pro'])
        ->and($data['datasets'][0]['data'])->toBe([3, 1]);
});

it('surfaces signup counts for the 7 and 30 day windows', function (): void {
    User::factory()->count(3)->create(['created_at' => now()->subDays(3)]);
    User::factory()->count(2)->create(['created_at' => now()->subDays(10)]);
    User::factory()->count(1)->create(['created_at' => now()->subDays(40)]);

    $data = callWidgetMethod(SignupsChartWidget::class, 'getData');

    expect($data['labels'])->toBe(['Last 7 days', 'Last 30 days'])
        ->and($data['datasets'][0]['data'])->toBe([3, 5]);
});

it('caches each widget query for 60 seconds using the widget class as the key', function (string $widgetClass, string $method): void {
    app()->instance('cache', Cache::store());

    Cache::shouldReceive('remember')
        ->once()
        ->with($widgetClass, 60, Mockery::type(Closure::class))
        ->andReturnUsing(fn (string $key, int $ttl, Closure $callback): mixed => $callback());

    callWidgetMethod($widgetClass, $method);
})->with([
    [UserStatsWidget::class, 'getStats'],
    [RevenueStatsWidget::class, 'getStats'],
    [SignupsChartWidget::class, 'getData'],
    [TiersChartWidget::class, 'getData'],
]);

it('does not re-run aggregate queries on a warm second widget render', function (string $widgetClass): void {
    $admin = makeDashboardOperator();
    $this->actingAs($admin);

    Cache::flush();

    Livewire::test($widgetClass)->assertSuccessful();

    DB::enableQueryLog();
    Livewire::test($widgetClass)->assertSuccessful();
    $queries = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();

    expect($queries->filter(function (string $query): bool {
        if (! str_contains($query, 'count(')) {
            return false;
        }

        return str_contains($query, 'from `users`')
            || str_contains($query, 'from `teams`')
            || str_contains($query, 'from `subscriptions`');
    }))->toBeEmpty();
})->with([
    UserStatsWidget::class,
    RevenueStatsWidget::class,
    SignupsChartWidget::class,
    TiersChartWidget::class,
]);

it('user stats widget renders without error', function (): void {
    $admin = makeDashboardOperator();
    $this->actingAs($admin);

    Cache::flush();

    Livewire::test(UserStatsWidget::class)->assertSuccessful();
});

it('revenue stats widget renders without error', function (): void {
    $admin = makeDashboardOperator();
    $this->actingAs($admin);

    Cache::flush();

    Livewire::test(RevenueStatsWidget::class)->assertSuccessful();
});

it('tiers chart widget renders without error', function (): void {
    $admin = makeDashboardOperator();
    $this->actingAs($admin);

    Cache::flush();

    Livewire::test(TiersChartWidget::class)->assertSuccessful();
});

it('signups chart widget renders without error', function (): void {
    $admin = makeDashboardOperator();
    $this->actingAs($admin);

    Cache::flush();

    Livewire::test(SignupsChartWidget::class)->assertSuccessful();
});

it('does not re-run aggregate widget queries after the dashboard cache is warm', function (): void {
    $admin = makeDashboardOperator();
    $this->actingAs($admin);

    Cache::flush();
    warmDashboardWidgetCache();

    DB::enableQueryLog();
    $this->get('/admin')->assertSuccessful();
    $queries = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();

    expect($queries->filter(function (string $query): bool {
        if (! str_contains($query, 'count(')) {
            return false;
        }

        return str_contains($query, 'from `users`')
            || str_contains($query, 'from `teams`')
            || str_contains($query, 'from `subscriptions`');
    }))->toBeEmpty();
});

it('shows the dashboard widgets on the operator panel', function (): void {
    $admin = makeDashboardOperator();
    $this->actingAs($admin);

    $this->get('/admin')
        ->assertSuccessful()
        ->assertSeeLivewire(UserStatsWidget::class)
        ->assertSeeLivewire(RevenueStatsWidget::class)
        ->assertSeeLivewire(SignupsChartWidget::class)
        ->assertSeeLivewire(TiersChartWidget::class);
});
