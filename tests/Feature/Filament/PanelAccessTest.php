<?php

declare(strict_types=1);

use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

it('redirects unauthenticated requests to /login', function (): void {
    $this->get('/admin')->assertRedirect('/login');
});

it('returns 403 for authenticated user without admin permission', function (): void {
    $user = User::factory()->createOne();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('allows authenticated admin to access the panel', function (): void {
    $admin = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $admin->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)->get('/admin')->assertSuccessful();
});

it('prevents an operator from revoking their own admin role', function (): void {
    $admin = User::factory()->createOne();

    expect($admin->canRevokeAdminRole($admin))->toBeFalse();
});

it('allows an operator to revoke another user\'s admin role', function (): void {
    $admin = User::factory()->createOne();
    $other = User::factory()->createOne();

    expect($admin->canRevokeAdminRole($other))->toBeTrue();
});
