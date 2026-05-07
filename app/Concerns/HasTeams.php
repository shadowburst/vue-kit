<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Team;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;

trait HasTeams
{
    use HasRoles;

    /**
     * @mago-expect analysis:non-existent-method
     * @mago-expect analysis:mixed-return-statement
     */
    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'owner_id');
    }

    public function isMemberOf(Team $team): bool
    {
        return $this->teams()->whereKey($team->getKey())->exists();
    }
}
