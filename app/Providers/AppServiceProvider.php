<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\Feature\Feature as FeatureEnum;
use App\Listeners\PurgeFeaturesOnSubscriptionChange;
use App\Models\Team;
use App\Policies\SubscriptionPolicy;
use App\Services\Team\TeamContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Cashier\Subscription;
use Laravel\Pennant\Feature;

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

        Feature::define(
            FeatureEnum::TeamMemberCap->value,
            fn (Team $team): int => (int) config("billing.tiers.{$team->tier->value}.member_cap", 0),
        );

        $this->configureDefaults();

        Event::listen(WebhookHandled::class, PurgeFeaturesOnSubscriptionChange::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        /** @mago-expect analysis:non-documented-method */
        Date::use(CarbonImmutable::class);

        JsonResource::withoutWrapping();

        Model::preventLazyLoading(! app()->isProduction());

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
