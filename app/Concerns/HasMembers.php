<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Enums\Role\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Permission\Models\Role as SpatieRole;

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
        // Subquery so role_id resolution stays lazy — direct ->model() lookup would
        // hit the DB at relation-build time and break model introspection (CI types).
        return $this->members()->wherePivotIn(
            'role_id',
            SpatieRole::query()->select('id')->where('name', Role::Owner->value),
        );
    }
}
