<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\Membership\ResetCurrentTeam;
use App\Models\Team;
use App\Models\User;

final class TeamObserver
{
    public function __construct(private readonly ResetCurrentTeam $resetCurrentTeam) {}

    /**
     * Reassign current_team_id before the nullOnDelete FK cascade fires so users
     * with another team end up with a valid id instead of null.
     */
    public function deleting(Team $team): void
    {
        User::query()
            ->where('current_team_id', $team->id)
            ->each(function (User $user) use ($team): void {
                $this->resetCurrentTeam->execute($user, $team);
            });
    }
}
