<?php

declare(strict_types=1);

use App\Actions\Team\CreateTeam;
use App\Enums\Permission\Permission;
use App\Enums\Role\Role;
use App\Models\User;
use App\Services\Team\TeamContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    Route::get('/_test/team', function (TeamContext $teamContext) {
        $user = Auth::user();
        assert($user === null || $user instanceof User, 'Auth::user() must be null or a User instance');
        $currentTeam = $teamContext->current();

        return response()->json([
            'currentTeam'   => $currentTeam?->id,
            'canViewAny'    => $user?->can(Permission::UserViewAny->value),
            'currentTeamId' => $user?->current_team_id,
        ]);
    })->middleware('web');
});

test('guest request passes through without team resolution', function (): void {
    get('/_test/team')->assertOk()->assertJson(['currentTeam' => null]);

    expect(app(TeamContext::class)->current())->toBeNull();
});

test('valid current_team_id stashes active team in container', function (): void {
    $user = User::factory()->createOne();
    $team = (new CreateTeam)->execute('Acme', $user);
    $user->update(['current_team_id' => $team->id]);

    actingAs($user)
        ->get('/_test/team')
        ->assertOk()
        ->assertJson(['currentTeam' => $team->id, 'currentTeamId' => $team->id]);
});

test('null current_team_id self-heals to first available team and persists', function (): void {
    $user = User::factory()->createOne(['current_team_id' => null]);
    $team = (new CreateTeam)->execute('Acme', $user);

    actingAs($user)
        ->get('/_test/team')
        ->assertOk()
        ->assertJson(['currentTeam' => $team->id]);

    expect($user->fresh()?->current_team_id)->toBe($team->id);
});

test('current_team_id pointing at non-member team self-heals to own team', function (): void {
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
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Acme', $owner);
    $owner->update(['current_team_id' => $team->id]);

    actingAs($owner)
        ->get('/_test/team')
        ->assertOk()
        ->assertJson(['canViewAny' => true]);
});

test('admin of active team has user.viewAny permission', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Acme', $owner);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin = User::factory()->createOne(['current_team_id' => $team->id]);
    $admin->assignRole(Role::Manager->value);

    actingAs($admin)
        ->get('/_test/team')
        ->assertOk()
        ->assertJson(['canViewAny' => true]);
});

test('member of active team has user.viewAny permission', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Acme', $owner);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $member = User::factory()->createOne(['current_team_id' => $team->id]);
    $member->assignRole(Role::Member->value);

    actingAs($member)
        ->get('/_test/team')
        ->assertOk()
        ->assertJson(['canViewAny' => true]);
});

test('user with no team membership is redirected and cannot access the protected resource', function (): void {
    $nonMember = User::factory()->createOne(['current_team_id' => null]);

    actingAs($nonMember)
        ->get('/_test/team')
        ->assertRedirect(route('teams.create'));
});
