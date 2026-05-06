<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasMembers;
use App\Enums\Subscription\SubscriptionTier;
use App\Observers\TeamObserver;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Billable;
use Laravel\Pennant\Feature;
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
 * @property-read array<string, mixed> $features
 * @property-read User $owner
 */
#[ObservedBy(TeamObserver::class)]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use Billable, HasFactory, HasMembers, HasSlug;

    protected $fillable = ['name', 'owner_id'];

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

    public function tier(): SubscriptionTier
    {
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
    }
}
