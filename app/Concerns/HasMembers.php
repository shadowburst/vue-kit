<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @mixin Team
 */
trait HasMembers
{
    public function members(): MorphToMany
    {
        $relation = $this->morphedByMany(User::class, 'model', 'model_has_roles', 'team_id', 'model_id');
        /** @mago-expect analysis:possibly-non-existent-property */
        $relation->whereKeyNot($this->owner_id);
        $relation->getQuery()->distinct();

        return $relation;
    }
}
