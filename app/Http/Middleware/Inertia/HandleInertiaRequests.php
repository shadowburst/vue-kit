<?php

declare(strict_types=1);

namespace App\Http\Middleware\Inertia;

use App\Data\Auth\AuthAbilitiesData;
use App\Enums\Permission\Permission;
use App\Http\Resources\User\UserResource;
use App\Models\Team;
use App\Models\User;
use App\Services\Team\TeamContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private readonly TeamContext $teamContext,
    ) {}

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var ?User $user */
        $user        = $request->user();
        $currentTeam = $this->teamContext->current();

        /** @var string $appName */
        $appName = config('app.name');

        return [
            ...parent::share($request),
            'name'        => $appName,
            'auth'        => [
                'user'         => $user ? UserResource::make($user) : null,
                'abilities'    => fn () => AuthAbilitiesData::fromUser($user, $currentTeam),
                'features'     => fn () => $currentTeam !== null ? $currentTeam->features : [],
                'subscription' => fn () => $this->subscriptionGracePeriodData($user, $currentTeam),
            ],
            'currentTeam' => $currentTeam !== null
                ? [
                    'id'   => $currentTeam->id,
                    'name' => $currentTeam->name,
                    'slug' => $currentTeam->slug,
                ] : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'locale'      => app()->getLocale(),
        ];
    }

    /**
     * @return array{grace_period: array{ends_at: string|null, at_risk_count: int}}|null
     */
    private function subscriptionGracePeriodData(?User $user, ?Team $currentTeam): ?array
    {
        if ($user === null || $currentTeam === null) {
            return null;
        }

        if (! $user->can(Permission::SubscriptionView->value)) {
            return null;
        }

        $subscription = $currentTeam->subscription('default');

        if ($subscription === null || ! $subscription->onGracePeriod()) {
            return null;
        }

        /** @var int $freeCap */
        $freeCap       = config('billing.tiers.free.member_cap');
        $nonOwnerCount = $currentTeam->members()->whereKeyNot($currentTeam->owner_id)->count();

        if ($nonOwnerCount <= $freeCap) {
            return null;
        }

        /** @mago-expect analysis:non-documented-property */
        /** @mago-expect analysis:mixed-method-access */
        /** @var string|null $endsAt */
        $endsAt = $subscription->ends_at?->toIso8601String();

        return [
            'grace_period' => [
                'ends_at'       => $endsAt,
                'at_risk_count' => $nonOwnerCount - $freeCap,
            ],
        ];
    }
}
