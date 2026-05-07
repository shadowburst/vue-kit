<?php

declare(strict_types=1);

use App\Actions\Membership\AssignMembership;
use App\Actions\Team\CreateTeam;
use App\Enums\Role\Role;
use App\Models\User;

it('reassigns current_team_id for all members when a team is deleted', function (): void {
    $ownerA = User::factory()->createOne();
    $teamA  = (new CreateTeam)->execute('Team A', $ownerA);

    $ownerB = User::factory()->createOne();
    $teamB  = (new CreateTeam)->execute('Team B', $ownerB);

    // member1 belongs to both teamA (current) and teamB
    $member1 = User::factory()->createOne(['current_team_id' => $teamA->id]);
    (new AssignMembership)->execute($member1, $teamA, Role::Member);
    (new AssignMembership)->execute($member1, $teamB, Role::Member);

    // member2 belongs only to teamA (current)
    $member2 = User::factory()->createOne(['current_team_id' => $teamA->id]);
    (new AssignMembership)->execute($member2, $teamA, Role::Member);

    $teamA->delete();

    expect($member1->fresh()?->current_team_id)->toBe($teamB->id)->and($member2->fresh()?->current_team_id)->toBeNull();
});
