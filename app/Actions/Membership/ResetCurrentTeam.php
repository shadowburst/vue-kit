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
        if ($this->isValid($user, $excluding)) {
            return;
        }

        $newTeamId = $user->teams()
            ->when($excluding !== null, fn ($q) => $q->where('teams.id', '!=', $excluding->id))
            ->first()
            ?->id;

        $user->update(['current_team_id' => $newTeamId]);
    }

    private function isValid(User $user, ?Team $excluding): bool
    {
        if ($user->current_team_id === null) {
            return false;
        }

        if ($excluding !== null && $user->current_team_id === $excluding->id) {
            return false;
        }

        return $user->teams()->where('teams.id', $user->current_team_id)->exists();
    }
}
