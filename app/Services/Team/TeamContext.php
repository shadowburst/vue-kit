<?php

declare(strict_types=1);

namespace App\Services\Team;

use App\Models\Team;
use RuntimeException;

final class TeamContext
{
    private ?Team $team = null;

    public function setTeam(?Team $team): void
    {
        $this->team = $team;
    }

    public function current(): ?Team
    {
        return $this->team;
    }

    public function currentOrFail(): Team
    {
        if ($this->team === null) {
            throw new RuntimeException(
                'No current team is set. Ensure SetCurrentTeam middleware ran for this request.',
            );
        }

        return $this->team;
    }
}
