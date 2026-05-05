<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Team;
use App\Models\User;

final class TeamObserver
{
    /**
     * Reassign current_team_id before the nullOnDelete FK cascade fires so users
     * with another team end up with a valid id instead of null.
     */
    public function deleting(Team $team): void
    {
        User::query()
            ->where('current_team_id', $team->id)
            ->each(function (User $user) use ($team): void {
                $newTeamId = $user
                    ->teams()
                    ->where('teams.id', '!=', $team->id)
                    ->first()
                    ?->id;

                $user->update(['current_team_id' => $newTeamId]);
            });
    }
}
