<?php

declare(strict_types=1);

use App\Enums\Feature\Feature as FeatureEnum;
use App\Models\Team;
use Laravel\Pennant\Feature;

it('returns the TeamMemberCap feature with its configured value when no other features are defined', function (): void {
    $team = Team::factory()->createOne();

    expect($team->features)->toBe([FeatureEnum::TeamMemberCap->value => 0]);
});

it('returns the resolved feature map when a test-only feature is registered against a Team', function (): void {
    $team = Team::factory()->createOne();

    Feature::define('test-feature', fn (Team $team) => true);

    expect($team->features)->toBe([
        FeatureEnum::TeamMemberCap->value => 0,
        'test-feature'                    => true,
    ]);
});
