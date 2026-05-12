<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Activity as ActivityModel;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property string|null $stripe_price
 * @property Carbon|null $trial_ends_at
 * @property-read Team $owner
 */
class Subscription extends \Laravel\Cashier\Subscription
{
    use LogsActivity;

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
}
