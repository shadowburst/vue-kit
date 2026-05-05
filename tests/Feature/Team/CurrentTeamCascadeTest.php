<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeam;
use App\Enums\Role\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\seed;

it('reassigns current_team_id when removing a member who belongs to multiple teams', function (): void {
    seed(RolePermissionSeeder::class);

    $ownerA = User::factory()->createOne();
    $teamA  = (new CreateTeam)->execute('Team A', $ownerA);

    $ownerB = User::factory()->createOne();
    $teamB  = (new CreateTeam)->execute('Team B', $ownerB);

    $member = User::factory()->createOne(['current_team_id' => $teamA->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($teamA->id);
    $member->assignRole(RoleName::Member->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($teamB->id);
    $member->assignRole(RoleName::Member->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($teamA->id);
    $member->removeRole(RoleName::Member->value);

    expect($member->fresh()?->current_team_id)->toBe($teamB->id);
});

it('sets current_team_id to null when removing the only role in the current team with no other teams', function (): void {
    seed(RolePermissionSeeder::class);

    $owner = User::factory()->createOne();
    $teamA = (new CreateTeam)->execute('Team A', $owner);

    $member = User::factory()->createOne(['current_team_id' => $teamA->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($teamA->id);
    $member->assignRole(RoleName::Member->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($teamA->id);
    $member->removeRole(RoleName::Member->value);

    expect($member->fresh()?->current_team_id)->toBeNull();
});

it('leaves current_team_id unchanged when removing a role in a non-current team', function (): void {
    seed(RolePermissionSeeder::class);

    $ownerA = User::factory()->createOne();
    $teamA  = (new CreateTeam)->execute('Team A', $ownerA);

    $ownerB = User::factory()->createOne();
    $teamB  = (new CreateTeam)->execute('Team B', $ownerB);

    $member = User::factory()->createOne(['current_team_id' => $teamA->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($teamA->id);
    $member->assignRole(RoleName::Member->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($teamB->id);
    $member->assignRole(RoleName::Member->value);

    // Remove from teamB — not the current team
    app(PermissionRegistrar::class)->setPermissionsTeamId($teamB->id);
    $member->removeRole(RoleName::Member->value);

    expect($member->fresh()?->current_team_id)->toBe($teamA->id);
});

it('does not change current_team_id when user still holds another role in the same team', function (): void {
    seed(RolePermissionSeeder::class);

    $owner = User::factory()->createOne();
    $teamA = (new CreateTeam)->execute('Team A', $owner);

    // Assign Owner + Admin to the same user in teamA
    app(PermissionRegistrar::class)->setPermissionsTeamId($teamA->id);
    $owner->assignRole(RoleName::Admin->value);
    $owner->update(['current_team_id' => $teamA->id]);

    // Remove only Admin — Owner role remains, so membership persists
    app(PermissionRegistrar::class)->setPermissionsTeamId($teamA->id);
    $owner->removeRole(RoleName::Admin->value);

    expect($owner->fresh()?->current_team_id)->toBe($teamA->id);
});

it('reassigns current_team_id for all members when a team is deleted', function (): void {
    seed(RolePermissionSeeder::class);

    $ownerA = User::factory()->createOne();
    $teamA  = (new CreateTeam)->execute('Team A', $ownerA);

    $ownerB = User::factory()->createOne();
    $teamB  = (new CreateTeam)->execute('Team B', $ownerB);

    // member1 belongs to both teamA (current) and teamB
    $member1 = User::factory()->createOne(['current_team_id' => $teamA->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($teamA->id);
    $member1->assignRole(RoleName::Member->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId($teamB->id);
    $member1->assignRole(RoleName::Member->value);

    // member2 belongs only to teamA (current)
    $member2 = User::factory()->createOne(['current_team_id' => $teamA->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($teamA->id);
    $member2->assignRole(RoleName::Member->value);

    $teamA->delete();

    expect($member1->fresh()?->current_team_id)->toBe($teamB->id)->and($member2->fresh()?->current_team_id)->toBeNull();
});
