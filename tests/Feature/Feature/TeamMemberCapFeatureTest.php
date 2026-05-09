<?php

declare(strict_types=1);

use App\Enums\Feature\Feature as FeatureEnum;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature;

it('resolves the configured cap for a Free team (no subscription)', function (): void {
    $team = Team::factory()->createOne();

    expect(Feature::for($team)->value(FeatureEnum::TeamMemberCap->value))->toBe(0);
});

it('resolves the configured cap for a Pro team', function (): void {
    $team = Team::factory()->createOne();

    DB::table('subscriptions')->insert([
        'team_id'       => $team->id,
        'type'          => 'default',
        'stripe_id'     => 'sub_cap_test_'.$team->id,
        'stripe_status' => 'active',
        'stripe_price'  => config('billing.tiers.pro.monthly'),
        'created_at'    => now()->toDateTimeString(),
        'updated_at'    => now()->toDateTimeString(),
    ]);

    expect(Feature::for($team)->value(FeatureEnum::TeamMemberCap->value))->toBe(3);
});
