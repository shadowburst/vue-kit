<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeam;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('guests are redirected to login when accessing team index', function (): void {
    get(route('teams.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can visit the teams index page', function (): void {
    $user = User::factory()->createOne();

    actingAs($user)
        ->get(route('teams.index'))
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('teams/Index'),
        );
});

test('teams index returns paginated teams data', function (): void {
    $user = User::factory()->createOne();
    (new CreateTeam)->execute('Alpha', $user);
    (new CreateTeam)->execute('Beta', $user);

    actingAs($user)
        ->get(route('teams.index'))
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('teams/Index')
                ->has('teams.data', 2)
                ->where('teams.data.0.name', 'Alpha')
                ->where('teams.data.1.name', 'Beta'),
        );
});

test('teams index response contains pagination meta', function (): void {
    $user = User::factory()->createOne();
    (new CreateTeam)->execute('Acme', $user);

    actingAs($user)
        ->get(route('teams.index'))
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->has('teams.meta')
                ->where('teams.meta.current_page', 1),
        );
});

test('guests are redirected to login when accessing team create page', function (): void {
    get(route('teams.create'))
        ->assertRedirect(route('login'));
});

test('authenticated users can visit the teams create page', function (): void {
    $user = User::factory()->createOne();

    actingAs($user)
        ->get(route('teams.create'))
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('teams/Create'),
        );
});

test('guests are redirected to login when posting to team store', function (): void {
    post(route('teams.store'), ['name' => 'New Team'])
        ->assertRedirect(route('login'));
});

test('authenticated user can create a team', function (): void {
    $user = User::factory()->createOne();

    actingAs($user)
        ->post(route('teams.store'), ['name' => 'Acme Corp'])
        ->assertRedirect(route('teams.index'));

    expect(Team::query()->where('name', 'Acme Corp')->exists())->toBeTrue();
});

test('team name is required', function (): void {
    $user = User::factory()->createOne();

    actingAs($user)
        ->post(route('teams.store'), ['name' => ''])
        ->assertInvalid(['name']);
});

test('team name may not exceed 255 characters', function (): void {
    $user = User::factory()->createOne();

    actingAs($user)
        ->post(route('teams.store'), ['name' => str_repeat('a', 256)])
        ->assertInvalid(['name']);
});
