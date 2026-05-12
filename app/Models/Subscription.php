<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Permission\Permission;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

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
        if (auth()->check() && auth()->user()->can(Permission::Admin->value)) {
            $activity->log_name = 'admin';
        }
    }
}
