<?php

declare(strict_types=1);

namespace App\Actions\Teams;

use App\Enums\RoleName;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Spatie\QueueableAction\QueueableAction;

final class CreateTeam
{
    use QueueableAction;

    public function execute(string $name, User $creator): Team
    {
        return DB::transaction(function () use ($name, $creator): Team {
            $team = Team::query()->create(['name' => $name]);

            app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
            $creator->assignRole(RoleName::Owner->value);

            return $team;
        });
    }
}
