<?php

declare(strict_types=1);

use App\Models\Team;
use Laravel\Pennant\Feature;

it('returns an empty map when no features are defined', function (): void {
    $team = Team::factory()->createOne();

    expect($team->features)->toBeEmpty();
});

it('returns the resolved feature map when a test-only feature is registered against a Team', function (): void {
    $team = Team::factory()->createOne();

    Feature::define('test-feature', fn (Team $team) => true);

    expect($team->features)->toBe(['test-feature' => true]);
});
