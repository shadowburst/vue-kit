<?php

declare(strict_types=1);

namespace App\Http\Middleware\Inertia;

use App\Data\Auth\AuthAbilitiesData;
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
        /** @var User|null $user */
        $user        = $request->user();
        $currentTeam = $this->teamContext->current();

        /** @var string $appName */
        $appName = config('app.name');

        return [
            ...parent::share($request),
            'name'        => $appName,
            'auth'        => [
                'user'      => $user instanceof User
                    ? [
                        ...$user->toArray(),
                        'teams'       => $user->teams()->get(['teams.id', 'teams.name', 'teams.slug']),
                        'permissions' => $user->getAllPermissions()->pluck('name')->sort()->values()->all(),
                    ] : null,
                'abilities' => fn () => AuthAbilitiesData::fromUser($user),
                'features'  => fn () => $currentTeam !== null ? $currentTeam->features : [],
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
}
