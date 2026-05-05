<?php

declare(strict_types=1);

use App\Actions\Teams\CreateTeam;
use App\Enums\RoleName;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Features;

beforeEach(function (): void {
    skip_unless_fortify_has(Features::registration());
    $this->seed(RolePermissionSeeder::class);
});

test('signup creates one user, one team, and one owner role assignment', function (): void {
    $this->post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::where('email', 'test@example.com')->firstOrFail();

    $hasOwnerRole = DB::table('model_has_roles')
        ->where('model_has_roles.model_id', $user->id)
        ->where('model_has_roles.model_type', $user->getMorphClass())
        ->where('model_has_roles.team_id', $user->current_team_id)
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('roles.name', RoleName::Owner->value)
        ->exists();

    expect(Team::count())
        ->toBe(1)
        ->and($user->current_team_id)
        ->toBe(Team::first()->id)
        ->and($hasOwnerRole)
        ->toBeTrue();
});

test('signup team name resolves from the en locale', function (): void {
    $this->post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(Team::first()->name)->toBe('App');
});

test('signup team name resolves from the fr locale when accept-language is fr', function (): void {
    $this->post(
        route('register.store'),
        [
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'password',
            'password_confirmation' => 'password',
        ],
        ['Accept-Language' => 'fr'],
    );

    expect(Team::first()->name)->toBe('Application');
});

test('signup sets current_team_id on the created user', function (): void {
    $this->post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    /** @var User $user */
    $user = User::where('email', 'test@example.com')->firstOrFail();

    expect($user->current_team_id)->toBe(Team::query()->firstOrFail()->id);
});

test('signup rolls back the entire flow when team creation fails', function (): void {
    // CreateTeam is final so Mockery cannot subclass it; bind a throwing anonymous class instead.
    // $test captures $this before the anonymous class to keep Mago's scope analysis correct.
    $test = $this;

    app()->instance(CreateTeam::class, new class {
        public function execute(string $name, User $creator): never
        {
            throw new RuntimeException('team creation failed');
        }
    });

    $test->post(route('register.store'), [
        'name'                  => 'Doomed User',
        'email'                 => 'doomed@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::where('email', 'doomed@example.com')->exists())
        ->toBeFalse()
        ->and(Team::count())
        ->toBe(0)
        ->and(DB::table('model_has_roles')->count())
        ->toBe(0);
});
