<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasMembers;
use App\Enums\Feature\Feature as FeatureEnum;
use App\Enums\Subscription\SubscriptionTier;
use App\Observers\TeamObserver;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Laravel\Cashier\Billable;
use Laravel\Pennant\Feature;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Activity as ActivityModel;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $name
 * @property string $slug
 * @property string|null $stripe_id
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property Carbon|null $trial_ends_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read array<string, mixed> $features
 * @property-read User $owner
 * @property-read Collection<int, User> $members
 * @property-read int $members_count
 * @property-read SubscriptionTier $tier
 */
#[ObservedBy(TeamObserver::class)]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use Billable, HasFactory, HasMembers, HasSlug, LogsActivity, SoftDeletes;

    protected $fillable = ['name', 'owner_id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function beforeActivityLogged(Activity $activity, string $eventName): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user?->hasAdminRole() === true && $activity instanceof ActivityModel) {
            $activity->setAttribute('log_name', 'admin');
        }
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * @return Attribute<array<string, mixed>, never>
     */
    protected function features(): Attribute
    {
        return Attribute::get(fn (): array => Feature::for($this)->all());
    }

    /**
     * @return Attribute<int, never>
     */
    protected function membersCount(): Attribute
    {
        return Attribute::get(fn (?int $value): int => $value ?? (int) $this->members()->count());
    }

    public function isOverCap(): bool
    {
        $cap = (int) Feature::for($this)->value(FeatureEnum::TeamMemberCap->value);

        return $this->members_count > $cap;
    }

    public function canTransitionTo(SubscriptionTier $target): bool
    {
        $cap = Config::integer("billing.tiers.{$target->value}.member_cap");

        return $this->members_count <= $cap;
    }

    /**
     * @return Attribute<SubscriptionTier, never>
     */
    protected function tier(): Attribute
    {
        return Attribute::get(function (): SubscriptionTier {
            if (! $this->subscribed('default')) {
                return SubscriptionTier::Free;
            }

            $subscription = $this->subscription('default');

            if ($subscription === null) {
                return SubscriptionTier::Free;
            }

            return SubscriptionTier::fromStripePriceId(
                (string) ($subscription->getAttribute('stripe_price') ?? ''),
            );
        })->shouldCache();
    }
}
