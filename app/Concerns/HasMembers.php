<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\Role\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @mixin Model
 */
trait HasMembers
{
    public function members(): MorphToMany
    {
        $relation = $this->morphedByMany(User::class, 'model', 'model_has_roles', 'team_id', 'model_id');
        $relation->getQuery()->distinct();

        return $relation;
    }

    public function owners(): MorphToMany
    {
        return $this->members()->wherePivot('role_id', Role::Owner->model()->getKey());
    }
}
