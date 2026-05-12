<?php

declare(strict_types=1);

use App\Actions\Admin\GrantAdminRole;
use App\Actions\Team\CreateTeam;
use App\Enums\Role\Role;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Filament\Resources\UserResource\RelationManagers\MembershipsRelationManager;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity as ActivityModel;
use Spatie\Permission\PermissionRegistrar;

function makeOperator(): User
{
    $admin = User::factory()->createOne();
    $team = Team::factory()->createOne(['owner_id' => $admin->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);

    return $admin;
}

it('admin can list users', function (): void {
    $admin = makeOperator();
    $users = User::factory()->count(3)->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords($users);
});

it('non-admin cannot access user list', function (): void {
    $user = User::factory()->createOne();

    $this->actingAs($user)->get('/admin/users')->assertForbidden();
});

it('admin can edit user profile fields', function (): void {
    $admin = makeOperator();
    $target = User::factory()->createOne();

    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->fillForm([
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->fresh()->name)->toBe('Updated Name')
        ->and($target->fresh()->email)->toBe('updated@example.com');
});

it('password is updated when provided', function (): void {
    $admin = makeOperator();
    $target = User::factory()->createOne();
    $oldHash = $target->password;

    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->fillForm(['password' => 'new-secret-password'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->fresh()->password)->not->toBe($oldHash);
});

it('password is unchanged when left blank', function (): void {
    $admin = makeOperator();
    $target = User::factory()->createOne();
    $oldHash = $target->password;

    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->fillForm(['name' => 'Only Name Changed'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($target->fresh()->password)->toBe($oldHash);
});

it('grant admin action grants the admin role and logs activity', function (): void {
    $admin = makeOperator();
    $target = User::factory()->createOne();
    Team::factory()->createOne(['owner_id' => $target->id]);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('grantAdmin', $target)
        ->assertNotified();

    $activity = ActivityModel::where('log_name', 'admin')
        ->where('description', 'admin.grant')
        ->where('subject_id', $target->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($admin->id);
});

it('revoke admin action revokes the admin role and logs activity', function (): void {
    $admin = makeOperator();
    $target = User::factory()->createOne();
    $team = Team::factory()->createOne(['owner_id' => $target->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $target->assignRole(Role::Admin->value);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('revokeAdmin', $target)
        ->assertNotified();

    $activity = ActivityModel::where('log_name', 'admin')
        ->where('description', 'admin.revoke')
        ->where('subject_id', $target->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($admin->id);

    $stillHasRole = DB::table('model_has_roles')
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->where('model_has_roles.model_id', $target->id)
        ->where('roles.name', Role::Admin->value)
        ->exists();

    expect($stillHasRole)->toBeFalse();
});

it('revoke admin action is hidden for the authenticated operator themselves', function (): void {
    $admin = makeOperator();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('revokeAdmin', $admin);
});

it('grant admin action is hidden when user is already an admin', function (): void {
    $admin = makeOperator();
    $target = User::factory()->createOne();
    $team = Team::factory()->createOne(['owner_id' => $target->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $target->assignRole(Role::Admin->value);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('grantAdmin', $target);
});

it('grant admin action is hidden when user owns no team', function (): void {
    $admin = makeOperator();
    $target = User::factory()->createOne();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('grantAdmin', $target);
});

it('soft-delete is refused when user owns active teams', function (): void {
    $admin = makeOperator();
    $target = User::factory()->createOne();
    Team::factory()->createOne(['owner_id' => $target->id]);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('delete', $target);

    expect($target->fresh()->deleted_at)->toBeNull();
});

it('soft-delete is refused when user still owns soft-deleted teams', function (): void {
    $admin = makeOperator();
    $target = User::factory()->createOne();
    $team = Team::factory()->createOne(['owner_id' => $target->id]);
    $team->delete();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('delete', $target);

    expect($target->fresh()->deleted_at)->toBeNull();
});

it('edit page soft-delete is refused when user still owns soft-deleted teams', function (): void {
    $admin = makeOperator();
    $target = User::factory()->createOne();
    $team = Team::factory()->createOne(['owner_id' => $target->id]);
    $team->delete();

    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->callAction('delete');

    expect($target->fresh()->deleted_at)->toBeNull();
});

it('bulk soft-delete skips users who still own teams', function (): void {
    $admin = makeOperator();
    $blocked = User::factory()->createOne();
    $allowed = User::factory()->createOne();
    Team::factory()->createOne(['owner_id' => $blocked->id]);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableBulkAction('delete', [$blocked, $allowed]);

    expect($blocked->fresh()->deleted_at)->toBeNull()
        ->and(User::withTrashed()->find($allowed->id)?->deleted_at)->not->toBeNull();
});

it('soft-delete succeeds when user owns no teams', function (): void {
    $admin = makeOperator();
    $target = User::factory()->createOne();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('delete', $target);

    expect(User::withTrashed()->find($target->id)->deleted_at)->not->toBeNull();
});

it('force-delete is refused when user still has team memberships', function (): void {
    $admin = makeOperator();
    $target = User::factory()->createOne();
    $target->delete();

    $memberTeam = Team::factory()->createOne(['owner_id' => $admin->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($memberTeam->id);
    $target->assignRole(Role::Member->value);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('forceDelete', $target);

    expect(User::withTrashed()->find($target->id))->not->toBeNull();
});

it('force-delete is refused when user memberships only remain on soft-deleted teams', function (): void {
    $admin = makeOperator();
    $target = User::factory()->createOne();
    $memberTeam = app(CreateTeam::class)->execute('Member Team', $admin);

    app(PermissionRegistrar::class)->setPermissionsTeamId($memberTeam->id);
    $target->assignRole(Role::Member->value);

    $memberTeam->delete();
    $target->delete();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('forceDelete', $target);

    expect(User::withTrashed()->find($target->id))->not->toBeNull();
});

it('force-delete is refused when user owns soft-deleted teams', function (): void {
    $admin = makeOperator();
    $target = User::factory()->createOne();
    $ownedTeam = Team::factory()->createOne(['owner_id' => $target->id]);
    $ownedTeam->delete();
    $target->delete();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('forceDelete', $target);

    expect(User::withTrashed()->find($target->id))->not->toBeNull();
});

it('force-delete succeeds when user is trashed with no memberships and no owned teams', function (): void {
    $admin = makeOperator();
    $target = User::factory()->createOne();
    $target->delete();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('forceDelete', $target);

    expect(User::withTrashed()->find($target->id))->toBeNull();
});

it('profile edit writes an activity log entry with log_name=admin', function (): void {
    $admin = makeOperator();
    $target = User::factory()->createOne();

    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $target->getRouteKey()])
        ->fillForm(['name' => 'Log Me'])
        ->call('save');

    $activity = ActivityModel::where('log_name', 'admin')
        ->where('subject_type', User::class)
        ->where('subject_id', $target->id)
        ->where('description', 'updated')
        ->first();

    expect($activity)->not->toBeNull();
});

it('memberships relation manager refuses removing the owner from their owned team', function (): void {
    $admin = makeOperator();
    $owner = User::factory()->createOne();
    $team = app(CreateTeam::class)->execute('Owned Team', $owner);

    $this->actingAs($admin);

    Livewire::test(MembershipsRelationManager::class, [
        'ownerRecord' => $owner,
        'pageClass' => ViewUser::class,
    ])
        ->callTableAction('removeFromTeam', $team);

    $stillMember = DB::table('model_has_roles')
        ->where('team_id', $team->id)
        ->where('model_id', $owner->id)
        ->exists();

    expect($stillMember)->toBeTrue();
});

it('GrantAdminRole action class refuses when target has no owned team', function (): void {
    $target = User::factory()->createOne();

    expect(fn () => app(GrantAdminRole::class)->execute($target))
        ->toThrow(RuntimeException::class);
});
