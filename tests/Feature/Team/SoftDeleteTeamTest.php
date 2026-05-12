<?php

declare(strict_types=1);

use App\Actions\Membership\AssignMembership;
use App\Actions\Team\CreateTeam;
use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;

it('soft-deletes a team without hard-removing it', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('My Team', $owner);

    $team->delete();

    expect(Team::find($team->id))->toBeNull()
        ->and(Team::withTrashed()->find($team->id))->not->toBeNull();
});

it('soft-deleting a team nullifies current_team_id for users pointed at it', function (): void {
    $owner  = User::factory()->createOne();
    $team   = (new CreateTeam)->execute('My Team', $owner);
    $member = User::factory()->createOne(['current_team_id' => $team->id]);
    (new AssignMembership)->execute($member, $team, Role::Member);

    $team->delete();

    expect($member->fresh()?->current_team_id)->toBeNull();
});

it('force-deleting a team also nullifies current_team_id for users pointed at it', function (): void {
    $owner  = User::factory()->createOne();
    $team   = (new CreateTeam)->execute('My Team', $owner);
    $member = User::factory()->createOne(['current_team_id' => $team->id]);
    (new AssignMembership)->execute($member, $team, Role::Member);

    $team->forceDelete();

    expect($member->fresh()?->current_team_id)->toBeNull();
});
