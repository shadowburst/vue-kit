<?php

declare(strict_types=1);

use App\Enums\Role\Role;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Http\Middleware\Team\SetCurrentTeam;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Lab404\Impersonate\Services\ImpersonateManager;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity as ActivityModel;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\withoutMiddleware;

beforeEach(fn () => withoutMiddleware(SetCurrentTeam::class));

function makeImpersonationOperator(): User
{
    $admin = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $admin->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);

    return $admin;
}

it('impersonate action is hidden for admin targets', function (): void {
    $admin  = makeImpersonationOperator();
    $target = User::factory()->createOne();
    $team   = Team::factory()->createOne(['owner_id' => $target->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $target->assignRole(Role::Admin->value);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('impersonate', $target);
});

it('impersonate action is visible for non-admin targets', function (): void {
    $admin  = makeImpersonationOperator();
    $target = User::factory()->createOne();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionVisible('impersonate', $target);
});

it('impersonate action logs impersonation.start and sets session', function (): void {
    $admin  = makeImpersonationOperator();
    $target = User::factory()->createOne();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('impersonate', $target);

    $activity = ActivityModel::where('log_name', 'admin')
        ->where('description', 'impersonation.start')
        ->where('subject_id', $target->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($admin->id);

    expect(app(ImpersonateManager::class)->isImpersonating())->toBeTrue()
        ->and(app(ImpersonateManager::class)->getImpersonatorId())->toBe($admin->id);
});

it('impersonation switches auth to target user with their current_team_id', function (): void {
    $admin      = makeImpersonationOperator();
    $target     = User::factory()->createOne();
    $targetTeam = Team::factory()->createOne(['owner_id' => $target->id]);
    $target->update(['current_team_id' => $targetTeam->id]);

    $admin->impersonate($target);

    expect(auth()->id())->toBe($target->id)
        ->and(auth()->user()->current_team_id)->toBe($targetTeam->id);
});

it('leave endpoint logs impersonation.stop and redirects to target user page', function (): void {
    $admin  = makeImpersonationOperator();
    $target = User::factory()->createOne();

    $response = actingAs($target)
        ->withSession([
            'impersonated_by'        => $admin->id,
            'impersonator_guard'     => 'web',
            'impersonator_guard_using' => null,
        ])
        ->post(route('impersonate.leave'));

    $activity = ActivityModel::where('log_name', 'admin')
        ->where('description', 'impersonation.stop')
        ->where('subject_id', $target->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($admin->id);

    $response->assertRedirect();
});

it('Inertia shares impersonator data when impersonation is active', function (): void {
    $admin  = makeImpersonationOperator();
    $target = User::factory()->createOne();
    $team   = Team::factory()->createOne(['owner_id' => $target->id]);
    $target->update(['current_team_id' => $team->id]);

    actingAs($target)
        ->withSession([
            'impersonated_by'        => $admin->id,
            'impersonator_guard'     => 'web',
            'impersonator_guard_using' => null,
        ])
        ->get(route('dashboard'))
        ->assertInertia(
            /** @mago-expect analysis:non-documented-method */
            fn (AssertableInertia $page) => $page
                ->where('auth.impersonator.id', $admin->id)
                ->where('auth.impersonator.name', $admin->name),
        );
});

it('Inertia shares null impersonator when no impersonation is active', function (): void {
    $user = User::factory()->createOne();
    $team = Team::factory()->createOne(['owner_id' => $user->id]);
    $user->update(['current_team_id' => $team->id]);

    actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(
            /** @mago-expect analysis:non-documented-method */
            fn (AssertableInertia $page) => $page
                ->where('auth.impersonator', null),
        );
});
