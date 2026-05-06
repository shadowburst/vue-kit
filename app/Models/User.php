<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Data\User\UserSettingsData;
use App\Enums\Role\RoleName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property int|null $current_team_id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property \Illuminate\Support\Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property ?UserSettingsData $settings
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
#[Fillable(['name', 'email', 'password', 'settings', 'current_team_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'settings'                => UserSettingsData::class,
        ];
    }

    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    /** @mago-expect analysis:invalid-return-statement */
    public function teams(): BelongsToMany
    {
        $relation = $this
            ->belongsToMany(Team::class, 'model_has_roles', 'model_id', 'team_id')
            ->wherePivot('model_type', $this->getMorphClass());

        $relation->getQuery()->distinct();

        return $relation;
    }

    /** @mago-expect analysis:invalid-return-statement */
    public function ownedTeams(): BelongsToMany
    {
        $relation = $this
            ->belongsToMany(Team::class, 'model_has_roles', 'model_id', 'team_id')
            ->wherePivot('model_type', $this->getMorphClass());

        $relation
            ->getQuery()
            ->where('model_has_roles.role_id', function (QueryBuilder $query): void {
                $query
                    ->select('id')
                    ->from('roles')
                    ->where('name', RoleName::Owner->value)
                    ->where('guard_name', 'web')
                    ->limit(1);
            })
            ->distinct();

        return $relation;
    }
}
