<?php

declare(strict_types=1);

namespace App\Http\Controllers\Team;

use App\Actions\Team\CreateTeam;
use App\Data\Team\TeamCreateProps;
use App\Data\Team\TeamCreateRequest;
use App\Data\Team\TeamIndexProps;
use App\Data\Team\TeamResource;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;

final class TeamController extends Controller
{
    public function index(): Response
    {
        /** @var User $user */
        $user = auth()->user();

        $teams = TeamResource::collect(
            $user->teams()->with('subscriptions')->paginate(),
            PaginatedDataCollection::class,
        );

        return Inertia::render('teams/Index', new TeamIndexProps(teams: $teams));
    }

    public function create(): Response
    {
        return Inertia::render('teams/Create', new TeamCreateProps);
    }

    public function store(TeamCreateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        (new CreateTeam)->execute($request->name, $user);

        return redirect()->route('teams.index');
    }
}
