<?php

declare(strict_types=1);

use App\Enums\Role\Role;
use App\Http\Middleware\Team\SetCurrentTeam;
use App\Models\Team;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\withoutMiddleware;

beforeEach(fn () => withoutMiddleware(SetCurrentTeam::class));

function makeImpersonatedSession(User $target, User $operator): array
{
    return [
        'impersonated_by'          => $operator->id,
        'impersonator_guard'       => 'web',
        'impersonator_guard_using' => null,
    ];
}

function makeImpersonationRefuseOperator(): User
{
    $admin = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $admin->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);

    return $admin;
}

it('password update returns 403 during impersonation', function (): void {
    $operator = makeImpersonationRefuseOperator();
    $target   = User::factory()->createOne();
    Team::factory()->createOne(['owner_id' => $target->id]);

    actingAs($target)
        ->withSession(makeImpersonatedSession($target, $operator))
        ->put(route('user-password.update'), [
            'current_password'      => 'password',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])
        ->assertForbidden();
});

it('profile update (email) returns 403 during impersonation', function (): void {
    $operator = makeImpersonationRefuseOperator();
    $target   = User::factory()->createOne();
    Team::factory()->createOne(['owner_id' => $target->id]);

    actingAs($target)
        ->withSession(makeImpersonatedSession($target, $operator))
        ->patch(route('profile.update'), [
            'name'  => $target->name,
            'email' => 'new@example.com',
        ])
        ->assertForbidden();
});

it('account delete returns 403 during impersonation', function (): void {
    $operator = makeImpersonationRefuseOperator();
    $target   = User::factory()->createOne();
    Team::factory()->createOne(['owner_id' => $target->id]);

    actingAs($target)
        ->withSession(makeImpersonatedSession($target, $operator))
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertForbidden();
});

it('subscription cancel returns 403 during impersonation', function (): void {
    $operator = makeImpersonationRefuseOperator();
    $target   = User::factory()->createOne();
    Team::factory()->createOne(['owner_id' => $target->id]);

    actingAs($target)
        ->withSession(makeImpersonatedSession($target, $operator))
        ->post(route('teams.billing.cancel.store'))
        ->assertForbidden();
});

it('subscription resume returns 403 during impersonation', function (): void {
    $operator = makeImpersonationRefuseOperator();
    $target   = User::factory()->createOne();
    Team::factory()->createOne(['owner_id' => $target->id]);

    actingAs($target)
        ->withSession(makeImpersonatedSession($target, $operator))
        ->post(route('teams.billing.resume.store'))
        ->assertForbidden();
});

it('subscription checkout returns 403 during impersonation', function (): void {
    $operator = makeImpersonationRefuseOperator();
    $target   = User::factory()->createOne();
    Team::factory()->createOne(['owner_id' => $target->id]);

    actingAs($target)
        ->withSession(makeImpersonatedSession($target, $operator))
        ->post(route('teams.checkout.store'), ['interval' => 'monthly'])
        ->assertForbidden();
});

it('billing portal returns 403 during impersonation', function (): void {
    $operator = makeImpersonationRefuseOperator();
    $target   = User::factory()->createOne();
    Team::factory()->createOne(['owner_id' => $target->id]);

    actingAs($target)
        ->withSession(makeImpersonatedSession($target, $operator))
        ->get(route('teams.billing.portal.show'))
        ->assertForbidden();
});

it('mutation routes are not blocked for non-impersonated user', function (): void {
    $user = User::factory()->createOne();
    Team::factory()->createOne(['owner_id' => $user->id]);

    actingAs($user)
        ->patch(route('profile.update'), [
            'name'  => $user->name,
            'email' => 'noblock@example.com',
        ])
        ->assertStatus(302)
        ->assertRedirectToRoute('profile.edit');
});
