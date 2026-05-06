<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeam;
use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Features;

use function Pest\Laravel\post;

beforeEach(function (): void {
    skip_unless_fortify_has(Features::registration());
});

test('signup creates one user, one team, records owner_id, and assigns Admin role', function (): void {
    post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();
    $team = Team::query()->firstOrFail();

    $hasAdminRole = DB::table('model_has_roles')
        ->where('model_has_roles.model_id', $user->id)
        ->where('model_has_roles.model_type', $user->getMorphClass())
        ->where('model_has_roles.team_id', $user->current_team_id)
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('roles.name', Role::Admin->value)
        ->exists();

    expect(Team::query()->count())
        ->toBe(1)
        ->and($user->current_team_id)
        ->toBe($team->id)
        ->and($hasAdminRole)
        ->toBeTrue()
        ->and($team->owner_id)
        ->toBe($user->id);
});

test('signup team name resolves from the en locale', function (): void {
    post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(Team::query()->firstOrFail()->name)->toBe('App');
});

test('signup team name resolves from the fr locale when accept-language is fr', function (): void {
    post(
        route('register.store'),
        [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ],
        ['Accept-Language' => 'fr'],
    );

    expect(Team::query()->firstOrFail()->name)->toBe('Application');
});

test('signup sets current_team_id on the created user', function (): void {
    post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    /** @var User $user */
    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    expect($user->current_team_id)->toBe(Team::query()->firstOrFail()->id);
});

test('signup does not create a Stripe customer — teams.stripe_id is NULL', function (): void {
    post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(Team::query()->firstOrFail()->stripe_id)->toBeNull();
});

test('signup rolls back the entire flow when team creation fails', function (): void {
    // CreateTeam is final so Mockery cannot subclass it; bind a throwing anonymous class instead.
    app()->instance(CreateTeam::class, new class {
        public function execute(string $name, User $creator): never
        {
            throw new RuntimeException('team creation failed');
        }
    });

    post(route('register.store'), [
        'name'                  => 'Doomed User',
        'email'                 => 'doomed@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::query()->where('email', 'doomed@example.com')->exists())
        ->toBeFalse()
        ->and(Team::query()->count())
        ->toBe(0)
        ->and(DB::table('model_has_roles')->count())
        ->toBe(0);
});
