<?php

declare(strict_types=1);

use App\Actions\Teams\CreateTeam;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\put;

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

test('member can switch to a team they belong to and current_team_id persists', function (): void {
    $user  = User::factory()->create();
    $teamA = (new CreateTeam)->execute('Team A', $user);
    $teamB = (new CreateTeam)->execute('Team B', $user);
    $user->update(['current_team_id' => $teamA->id]);

    actingAs($user)
        ->put(route('current-team.update'), ['team_id' => $teamB->id])
        ->assertRedirect();

    expect($user->fresh()->current_team_id)->toBe($teamB->id);
});

test('non-member gets 403 and current_team_id is unchanged', function (): void {
    $owner     = User::factory()->create();
    $other     = User::factory()->create();
    $ownerTeam = (new CreateTeam)->execute('Owner Team', $owner);
    $otherTeam = (new CreateTeam)->execute('Other Team', $other);
    $owner->update(['current_team_id' => $ownerTeam->id]);

    actingAs($owner)
        ->put(route('current-team.update'), ['team_id' => $otherTeam->id])
        ->assertForbidden();

    expect($owner->fresh()->current_team_id)->toBe($ownerTeam->id);
});

test('unauthenticated request is redirected to login', function (): void {
    put(route('current-team.update'), ['team_id' => 1])
        ->assertRedirect(route('login'));
});
