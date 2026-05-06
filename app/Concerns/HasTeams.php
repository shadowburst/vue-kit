<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\Role\Role;
use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

trait HasTeams
{
    public function teams(): BelongsToMany
    {
        return $this->teamsQuery();
    }

    public function ownedTeams(): BelongsToMany
    {
        return $this->teamsQuery(Role::Owner);
    }

    public function isMemberOf(Team $team): bool
    {
        return $this->teamsQuery()->where('teams.id', $team->getKey())->exists();
    }

    public function isOwnerOf(Team $team): bool
    {
        return $this->teamsQuery(Role::Owner)->where('teams.id', $team->getKey())->exists();
    }

    public function roleIn(Team $team): ?Role
    {
        $roleName = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', $this->getKey())
            ->where('model_has_roles.model_type', $this->getMorphClass())
            ->where('model_has_roles.team_id', $team->getKey())
            ->value('roles.name');

        return $roleName !== null ? Role::tryFrom($roleName) : null;
    }

    private function teamsQuery(?Role $role = null): BelongsToMany
    {
        $relation = $this
            ->belongsToMany(Team::class, 'model_has_roles', 'model_id', 'team_id')
            ->wherePivot('model_type', $this->getMorphClass());

        if ($role !== null) {
            $relation
                ->getQuery()
                ->where('model_has_roles.role_id', function (QueryBuilder $query) use ($role): void {
                    $query
                        ->select('id')
                        ->from('roles')
                        ->where('name', $role->value)
                        ->where('guard_name', 'web')
                        ->limit(1);
                });
        }

        $relation->getQuery()->distinct();

        return $relation;
    }
}
