<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeam;
use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Features;

use function Pest\Laravel\post;
use function Pest\Laravel\seed;

beforeEach(function (): void {
    skip_unless_fortify_has(Features::registration());
    seed(RolePermissionSeeder::class);
});

test('signup creates one user, one team, and one owner role assignment', function (): void {
    post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    $hasOwnerRole = DB::table('model_has_roles')
        ->where('model_has_roles.model_id', $user->id)
        ->where('model_has_roles.model_type', $user->getMorphClass())
        ->where('model_has_roles.team_id', $user->current_team_id)
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('roles.name', Role::Owner->value)
        ->exists();

    expect(Team::query()->count())
        ->toBe(1)
        ->and($user->current_team_id)
        ->toBe(Team::query()->firstOrFail()->id)
        ->and($hasOwnerRole)
        ->toBeTrue();
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
