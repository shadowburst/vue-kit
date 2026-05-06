<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasMembers;
use App\Enums\Subscription\SubscriptionTier;
use App\Observers\TeamObserver;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $stripe_id
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property \Illuminate\Support\Carbon|null $trial_ends_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
#[ObservedBy(TeamObserver::class)]
class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use Billable, HasFactory, HasMembers, HasSlug;

    protected $fillable = ['name'];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
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

        return SubscriptionTier::fromStripePriceId((string) ($subscription->stripe_price ?? ''));
    }
}
