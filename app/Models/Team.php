<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Team extends Model
{
    use HasSlug;

    protected $fillable = ['name'];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function members(): BelongsToMany
    {
        return $this
            ->belongsToMany(User::class, 'model_has_roles', 'team_id', 'model_id')
            ->wherePivot('model_type', (new User)->getMorphClass())
            ->distinct();
    }

    public function owners(): BelongsToMany
    {
        return $this
            ->belongsToMany(User::class, 'model_has_roles', 'team_id', 'model_id')
            ->wherePivot('model_type', (new User)->getMorphClass())
            ->where('model_has_roles.role_id', function ($query) {
                $query
                    ->select('id')
                    ->from('roles')
                    ->where('name', RoleName::Owner->value)
                    ->where('guard_name', 'web')
                    ->limit(1);
            })
            ->distinct();
    }
}
