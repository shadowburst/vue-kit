<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\Role\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;

trait HasMembers
{
    public function members(): BelongsToMany
    {
        return $this->membersQuery();
    }

    public function owners(): BelongsToMany
    {
        return $this->membersQuery(Role::Owner);
    }

    private function membersQuery(?Role $role = null): BelongsToMany
    {
        $relation = $this
            ->belongsToMany(User::class, 'model_has_roles', 'team_id', 'model_id')
            ->wherePivot('model_type', (new User)->getMorphClass());

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
