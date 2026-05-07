<?php

declare(strict_types=1);

use App\Actions\Membership\AssignMembership;
use App\Actions\Membership\RemoveMembership;
use App\Actions\Team\CreateTeam;
use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('removes all team-scoped roles for the user', function (): void {
    $user = User::factory()->createOne();
    $team = Team::query()->create(['name' => 'Acme Corp']);

    (new AssignMembership)->execute($user, $team, Role::Member);
    (new RemoveMembership)->execute($user, $team);

    $hasAnyRole = DB::table('model_has_roles')
        ->where('model_id', $user->id)
        ->where('model_type', $user->getMorphClass())
        ->where('team_id', $team->id)
        ->exists();

    expect($hasAnyRole)->toBeFalse();
});

it('updates current_team_id to another team when it pointed at the removed team', function (): void {
    $ownerA = User::factory()->createOne();
    $teamA  = (new CreateTeam)->execute('Team A', $ownerA);

    $ownerB = User::factory()->createOne();
    $teamB  = (new CreateTeam)->execute('Team B', $ownerB);

    $member = User::factory()->createOne(['current_team_id' => $teamA->id]);
    (new AssignMembership)->execute($member, $teamA, Role::Member);
    (new AssignMembership)->execute($member, $teamB, Role::Member);

    (new RemoveMembership)->execute($member, $teamA);

    expect($member->fresh()?->current_team_id)->toBe($teamB->id);
});

it('clears current_team_id to null when the user has no other team', function (): void {
    $owner = User::factory()->createOne();
    $teamA = (new CreateTeam)->execute('Team A', $owner);

    $member = User::factory()->createOne(['current_team_id' => $teamA->id]);
    (new AssignMembership)->execute($member, $teamA, Role::Member);

    (new RemoveMembership)->execute($member, $teamA);

    expect($member->fresh()?->current_team_id)->toBeNull();
});

it('leaves current_team_id untouched when it pointed at a different team', function (): void {
    $ownerA = User::factory()->createOne();
    $teamA  = (new CreateTeam)->execute('Team A', $ownerA);

    $ownerB = User::factory()->createOne();
    $teamB  = (new CreateTeam)->execute('Team B', $ownerB);

    $member = User::factory()->createOne(['current_team_id' => $teamA->id]);
    (new AssignMembership)->execute($member, $teamA, Role::Member);
    (new AssignMembership)->execute($member, $teamB, Role::Member);

    (new RemoveMembership)->execute($member, $teamB);

    expect($member->fresh()?->current_team_id)->toBe($teamA->id);
});
