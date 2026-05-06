<?php

declare(strict_types=1);

use App\Actions\Membership\ResetCurrentTeam;
use App\Actions\Team\CreateTeam;
use App\Enums\Role\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\seed;

it('heals null current_team_id to the first available team', function (): void {
    seed(RolePermissionSeeder::class);

    $user = User::factory()->createOne(['current_team_id' => null]);
    $team = (new CreateTeam)->execute('Acme', $user);

    app(ResetCurrentTeam::class)->execute($user);

    expect($user->fresh()?->current_team_id)->toBe($team->id);
});

it('heals a stale current_team_id pointing at a team the user is not a member of', function (): void {
    seed(RolePermissionSeeder::class);

    $user     = User::factory()->createOne();
    $userTeam = (new CreateTeam)->execute('User Team', $user);

    $other     = User::factory()->createOne();
    $staleTeam = (new CreateTeam)->execute('Stale Team', $other);

    $user->update(['current_team_id' => $staleTeam->id]);

    app(ResetCurrentTeam::class)->execute($user);

    expect($user->fresh()?->current_team_id)->toBe($userTeam->id);
});

it('is a no-op when current_team_id points at a valid membership', function (): void {
    seed(RolePermissionSeeder::class);

    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Acme', $user);
    $user->update(['current_team_id' => $team->id]);

    app(ResetCurrentTeam::class)->execute($user);

    expect($user->fresh()?->current_team_id)->toBe($team->id);
});

it('heals current_team_id to another team when the current value matches the excluded team', function (): void {
    seed(RolePermissionSeeder::class);

    $ownerA = User::factory()->createOne();
    $teamA  = (new CreateTeam)->execute('Team A', $ownerA);

    $ownerB = User::factory()->createOne();
    $teamB  = (new CreateTeam)->execute('Team B', $ownerB);

    $user = User::factory()->createOne(['current_team_id' => $teamA->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($teamA->id);
    $user->assignRole(Role::Member->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($teamB->id);
    $user->assignRole(Role::Member->value);

    app(ResetCurrentTeam::class)->execute($user, $teamA);

    expect($user->fresh()?->current_team_id)->toBe($teamB->id);
});

it('sets current_team_id to null when the excluded team is the only available team', function (): void {
    seed(RolePermissionSeeder::class);

    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Only Team', $user);
    $user->update(['current_team_id' => $team->id]);

    app(ResetCurrentTeam::class)->execute($user, $team);

    expect($user->fresh()?->current_team_id)->toBeNull();
});
