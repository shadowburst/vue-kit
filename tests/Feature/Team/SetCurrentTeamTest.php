<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeam;
use App\Enums\Permission\PermissionName;
use App\Enums\Role\RoleName;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function (): void {
    Route::get('/_test/team', function () {
        $user = Auth::user();
        assert($user === null || $user instanceof User);
        $currentTeam = app()->has('currentTeam') ? app('currentTeam') : null;
        assert($currentTeam === null || $currentTeam instanceof Team);

        return response()->json([
            'currentTeam'   => $currentTeam?->id,
            'canViewAny'    => $user?->can(PermissionName::UserViewAny->value),
            'currentTeamId' => $user?->current_team_id,
        ]);
    })->middleware('web');
});

test('guest request passes through without team resolution', function (): void {
    get('/_test/team')->assertOk()->assertJson(['currentTeam' => null]);

    expect(app()->has('currentTeam'))->toBeFalse();
});

test('valid current_team_id stashes active team in container', function (): void {
    seed(RolePermissionSeeder::class);
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Acme', $user);
    $user->update(['current_team_id' => $team->id]);

    actingAs($user)
        ->get('/_test/team')
        ->assertOk()
        ->assertJson(['currentTeam' => $team->id, 'currentTeamId' => $team->id]);
});

test('null current_team_id self-heals to first available team and persists', function (): void {
    seed(RolePermissionSeeder::class);
    $user = User::factory()->createOne(['current_team_id' => null]);
    $team = (new CreateTeam)->execute('Acme', $user);

    actingAs($user)
        ->get('/_test/team')
        ->assertOk()
        ->assertJson(['currentTeam' => $team->id]);

    expect($user->fresh()?->current_team_id)->toBe($team->id);
});

test('current_team_id pointing at non-member team self-heals to own team', function (): void {
    seed(RolePermissionSeeder::class);

    $user     = User::factory()->createOne();
    $userTeam = (new CreateTeam)->execute('User Team', $user);

    $otherUser = User::factory()->createOne();
    $otherTeam = (new CreateTeam)->execute('Other Team', $otherUser);

    $user->update(['current_team_id' => $otherTeam->id]);

    actingAs($user)
        ->get('/_test/team')
        ->assertOk()
        ->assertJson(['currentTeam' => $userTeam->id]);

    expect($user->fresh()?->current_team_id)->toBe($userTeam->id);
});

test('user with zero teams is redirected to teams.create', function (): void {
    $user = User::factory()->createOne(['current_team_id' => null]);

    actingAs($user)
        ->get('/_test/team')
        ->assertRedirect(route('teams.create'));
});

test('teams.create route is exempt from the zero-team redirect to prevent loops', function (): void {
    $user = User::factory()->createOne(['current_team_id' => null]);

    // A user with no teams normally gets redirected to teams.create.
    // Hitting teams.create itself must not trigger another redirect.
    $response = actingAs($user)->get(route('teams.create'));

    /** @mago-expect analysis:non-documented-method */
    expect($response->status())->not->toBe(302);
});

test('owner of active team has user.viewAny permission', function (): void {
    seed(RolePermissionSeeder::class);
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Acme', $owner);
    $owner->update(['current_team_id' => $team->id]);

    actingAs($owner)
        ->get('/_test/team')
        ->assertOk()
        ->assertJson(['canViewAny' => true]);
});

test('admin of active team has user.viewAny permission', function (): void {
    seed(RolePermissionSeeder::class);
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Acme', $owner);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin = User::factory()->createOne(['current_team_id' => $team->id]);
    $admin->assignRole(RoleName::Admin->value);

    actingAs($admin)
        ->get('/_test/team')
        ->assertOk()
        ->assertJson(['canViewAny' => true]);
});

test('member of active team has user.viewAny permission', function (): void {
    seed(RolePermissionSeeder::class);
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Acme', $owner);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member = User::factory()->createOne(['current_team_id' => $team->id]);
    $member->assignRole(RoleName::Member->value);

    actingAs($member)
        ->get('/_test/team')
        ->assertOk()
        ->assertJson(['canViewAny' => true]);
});

test('user with no team membership is redirected and cannot access the protected resource', function (): void {
    seed(RolePermissionSeeder::class);
    $nonMember = User::factory()->createOne(['current_team_id' => null]);

    actingAs($nonMember)
        ->get('/_test/team')
        ->assertRedirect(route('teams.create'));
});
