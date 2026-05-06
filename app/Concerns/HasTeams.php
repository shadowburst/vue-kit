<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\Role\Role;
use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Traits\HasRoles;

trait HasTeams
{
    use HasRoles;

    public function ownedTeams(): BelongsToMany
    {
        return $this->teams()->wherePivot('role_id', Role::Owner->model()->getKey());
    }

    public function isMemberOf(Team $team): bool
    {
        return $this->teams()->whereKey($team->getKey())->exists();
    }
}
