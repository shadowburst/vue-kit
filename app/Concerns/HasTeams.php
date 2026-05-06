<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\Role\Role;
use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Traits\HasRoles;

trait HasTeams
{
    use HasRoles;

    public function ownedTeams(): BelongsToMany
    {
        // Subquery so role_id resolution stays lazy — direct ->model() lookup would
        // hit the DB at relation-build time and break model introspection (CI types).
        return $this->teams()->wherePivotIn(
            'role_id',
            SpatieRole::query()->select('id')->where('name', Role::Owner->value),
        );
    }

    public function isMemberOf(Team $team): bool
    {
        return $this->teams()->whereKey($team->getKey())->exists();
    }
}
