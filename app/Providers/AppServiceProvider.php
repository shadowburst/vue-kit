<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\PurgeFeaturesOnSubscriptionChange;
use App\Listeners\SyncCurrentTeamOnRoleDetached;
use App\Models\Team;
use App\Policies\SubscriptionPolicy;
use App\Services\Team\TeamContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Cashier\Subscription;
use Spatie\Permission\Events\RoleDetachedEvent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TeamContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::useCustomerModel(Team::class);

        Gate::policy(Subscription::class, SubscriptionPolicy::class);

        $this->configureDefaults();

        Event::listen(RoleDetachedEvent::class, SyncCurrentTeamOnRoleDetached::class);
        Event::listen(WebhookHandled::class, PurgeFeaturesOnSubscriptionChange::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        /** @mago-expect analysis:non-documented-method */
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null);
    }
}
