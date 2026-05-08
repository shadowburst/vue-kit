<?php

declare(strict_types=1);

namespace App\Actions\Team;

use App\Actions\Membership\AssignMembership;
use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\QueueableAction\QueueableAction;

final class CreateTeam
{
    use QueueableAction;

    /** @mago-expect analysis:mixed-return-statement */
    public function execute(string $name, User $creator): Team
    {
        return DB::transaction(function () use ($name, $creator): Team {
            $team = Team::query()->create(['name' => $name, 'owner_id' => $creator->id]);

            (new AssignMembership)->execute($creator, $team, Role::Manager);

            return $team;
        });
    }
}
