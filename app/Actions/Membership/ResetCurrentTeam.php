<?php

declare(strict_types=1);

namespace App\Actions\Membership;

use App\Models\Team;
use App\Models\User;
use Spatie\QueueableAction\QueueableAction;

final class ResetCurrentTeam
{
    use QueueableAction;

    public function execute(User $user, ?Team $excluding = null): void
    {
        if (! $this->needsReset($user, $excluding)) {
            return;
        }

        $teams = $user->teams();

        if ($excluding !== null) {
            $teams->where('teams.id', '!=', $excluding->id);
        }

        /** @var Team|null $nextTeam */
        $nextTeam = $teams->first();

        $newTeamId = $nextTeam?->id;

        $user->update(['current_team_id' => $newTeamId]);
    }

    private function needsReset(User $user, ?Team $excluding): bool
    {
        if ($user->current_team_id === null) {
            return true;
        }

        if ($excluding !== null && $user->current_team_id === $excluding->id) {
            return true;
        }

        return ! (bool) $user->teams()->where('teams.id', $user->current_team_id)->exists();
    }
}
