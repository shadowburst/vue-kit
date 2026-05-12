<?php

declare(strict_types=1);

use App\Actions\Admin\ChangeTeamOwner;
use App\Actions\Membership\ChangeMembershipRole;
use App\Actions\Membership\RemoveMembership;
use App\Actions\Team\CreateTeam;
use App\Enums\Role\Role;
use App\Filament\Resources\TeamResource\Pages\EditTeam;
use App\Filament\Resources\TeamResource\Pages\ListTeams;
use App\Filament\Resources\TeamResource\Pages\ViewTeam;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity as ActivityModel;
use Spatie\Permission\PermissionRegistrar;
use Stripe\StripeClient;

use function Pest\Laravel\actingAs;

function makeTeamOperator(): User
{
    $admin = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $admin->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $admin->assignRole(Role::Admin->value);

    return $admin;
}

it('admin can list teams', function (): void {
    $admin = makeTeamOperator();
    $teams = Team::factory()->count(3)->create();

    actingAs($admin);

    Livewire::test(ListTeams::class)
        ->assertCanSeeTableRecords($teams);
});

it('non-admin cannot access team list', function (): void {
    $user = User::factory()->createOne();

    actingAs($user)->get('/admin/teams')->assertForbidden();
});

it('admin can edit team name', function (): void {
    $admin = makeTeamOperator();
    $team  = Team::factory()->createOne();

    actingAs($admin);

    Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()])
        ->fillForm(['name' => 'Renamed Team'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($team->fresh()->name)->toBe('Renamed Team');
});

it('trashed teams are hidden by default and a trashed filter exists', function (): void {
    $admin   = makeTeamOperator();
    $active  = Team::factory()->createOne();
    $trashed = Team::factory()->createOne();
    $trashed->delete();

    actingAs($admin);

    Livewire::test(ListTeams::class)
        ->assertTableFilterExists('trashed')
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$trashed]);
});

it('change owner action reassigns owner_id', function (): void {
    $admin    = makeTeamOperator();
    $oldOwner = User::factory()->createOne();
    $team     = Team::factory()->createOne(['owner_id' => $oldOwner->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $oldOwner->assignRole(Role::Manager->value);

    $newOwner = User::factory()->createOne();
    $newOwner->assignRole(Role::Member->value);

    actingAs($admin);

    Livewire::test(ViewTeam::class, ['record' => $team->getRouteKey()])
        ->callAction('changeOwner', data: ['new_owner_id' => $newOwner->id])
        ->assertNotified();

    expect($team->fresh()->owner_id)->toBe($newOwner->id);
});

it('change owner action assigns manager role to new owner when missing', function (): void {
    $admin    = makeTeamOperator();
    $oldOwner = User::factory()->createOne();
    $team     = Team::factory()->createOne(['owner_id' => $oldOwner->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $oldOwner->assignRole(Role::Manager->value);

    $newOwner = User::factory()->createOne();
    $newOwner->assignRole(Role::Member->value);

    actingAs($admin);

    Livewire::test(ViewTeam::class, ['record' => $team->getRouteKey()])
        ->callAction('changeOwner', data: ['new_owner_id' => $newOwner->id])
        ->assertNotified();

    $hasManagerRole = DB::table('model_has_roles')
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->where('model_has_roles.team_id', $team->id)
        ->where('model_has_roles.model_id', $newOwner->id)
        ->where('roles.name', Role::Manager->value)
        ->exists();

    expect($hasManagerRole)->toBeTrue();
});

it('change owner action logs activity', function (): void {
    $admin    = makeTeamOperator();
    $oldOwner = User::factory()->createOne();
    $team     = Team::factory()->createOne(['owner_id' => $oldOwner->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $oldOwner->assignRole(Role::Manager->value);

    $newOwner = User::factory()->createOne();
    $newOwner->assignRole(Role::Member->value);

    actingAs($admin);

    Livewire::test(ViewTeam::class, ['record' => $team->getRouteKey()])
        ->callAction('changeOwner', data: ['new_owner_id' => $newOwner->id]);

    $activity = ActivityModel::where('log_name', 'admin')
        ->where('description', 'team.owner.change')
        ->where('subject_id', $team->id)
        ->first();

    expect($activity)
        ->not
        ->toBeNull()
        ->and($activity->causer_id)
        ->toBe($admin->id)
        ->and($activity->properties['old_owner_id'])
        ->toBe($oldOwner->id)
        ->and($activity->properties['new_owner_id'])
        ->toBe($newOwner->id);
});

it('soft-delete cancels active subscription and soft-deletes team', function (): void {
    $admin = makeTeamOperator();
    $team  = Team::factory()->createOne();
    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_test',
        'stripe_status' => 'active',
        'stripe_price'  => 'price_pro_monthly',
    ]);

    $fakeSubscriptions = new class {
        public int $cancelCount = 0;

        public function cancel(string $id, array $params = []): void
        {
            $this->cancelCount++;
        }
    };

    app()->bind(StripeClient::class, fn () => (object) ['subscriptions' => $fakeSubscriptions]);

    actingAs($admin);

    Livewire::test(ListTeams::class)
        ->callTableAction('delete', $team);

    expect(Team::find($team->id))
        ->toBeNull()
        ->and(Team::withTrashed()->find($team->id))
        ->not
        ->toBeNull()
        ->and($fakeSubscriptions->cancelCount)
        ->toBe(1);
});

it('restore brings team back from trashed state', function (): void {
    $admin = makeTeamOperator();
    $team  = Team::factory()->createOne();
    $team->delete();

    actingAs($admin);

    Livewire::test(ListTeams::class)
        ->callTableAction('restore', $team);

    expect(Team::find($team->id))->not->toBeNull();
});

it('force-delete is refused when team has members', function (): void {
    $admin  = makeTeamOperator();
    $owner  = User::factory()->createOne();
    $team   = Team::factory()->createOne(['owner_id' => $owner->id]);
    $member = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $owner->assignRole(Role::Manager->value);
    $member->assignRole(Role::Member->value);

    $team->delete();

    actingAs($admin);

    Livewire::test(ListTeams::class)
        ->callTableAction('forceDelete', $team);

    expect(Team::withTrashed()->find($team->id))->not->toBeNull();
});

it('force-delete is refused when team has an active subscription', function (): void {
    $admin = makeTeamOperator();
    $team  = Team::factory()->createOne();

    // Soft-delete first (no subscription at this point, so no Stripe API call).
    $team->delete();

    // Insert an active subscription after soft-delete to simulate an edge case
    // where the subscription was not properly cancelled during the soft-delete step.
    $team->subscriptions()->create([
        'type'          => 'default',
        'stripe_id'     => 'sub_active',
        'stripe_status' => 'active',
        'stripe_price'  => 'price_pro_monthly',
    ]);

    actingAs($admin);

    Livewire::test(ListTeams::class)
        ->callTableAction('forceDelete', $team);

    expect(Team::withTrashed()->find($team->id))->not->toBeNull();
});

it('force-delete succeeds when team is trashed with no members and no active subscription', function (): void {
    $admin = makeTeamOperator();
    $team  = Team::factory()->createOne();
    $team->delete();

    actingAs($admin);

    Livewire::test(ListTeams::class)
        ->callTableAction('forceDelete', $team);

    expect(Team::withTrashed()->find($team->id))->toBeNull();
});

it('force-delete succeeds when a trashed team only has the owner membership left', function (): void {
    $admin = makeTeamOperator();
    $owner = User::factory()->createOne();
    $team  = app(CreateTeam::class)->execute('Owner Only Team', $owner);
    $team->delete();

    actingAs($admin);

    Livewire::test(ListTeams::class)
        ->callTableAction('forceDelete', $team);

    expect(Team::withTrashed()->find($team->id))->toBeNull();
});

it('membership change role updates the scoped member role', function (): void {
    $owner  = User::factory()->createOne();
    $team   = Team::factory()->createOne(['owner_id' => $owner->id]);
    $member = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $owner->assignRole(Role::Manager->value);
    $member->assignRole(Role::Member->value);

    app(ChangeMembershipRole::class)->execute($member, $team, Role::Manager);

    expect(
        DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.team_id', $team->id)
            ->where('model_has_roles.model_id', $member->id)
            ->where('roles.name', Role::Manager->value)
            ->exists(),
    )->toBeTrue();
});

it('team owner is excluded from removable members', function (): void {
    $owner = User::factory()->createOne();
    $team  = Team::factory()->createOne(['owner_id' => $owner->id]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $owner->assignRole(Role::Manager->value);

    expect($team->members()->whereKey($owner->id)->exists())
        ->toBeFalse()
        ->and(
            DB::table('model_has_roles')
                ->where('team_id', $team->id)
                ->where('model_id', $owner->id)
                ->exists(),
        )
        ->toBeTrue();
});

it('membership remove succeeds for non-owner member', function (): void {
    $owner  = User::factory()->createOne();
    $team   = Team::factory()->createOne(['owner_id' => $owner->id]);
    $member = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $owner->assignRole(Role::Manager->value);
    $member->assignRole(Role::Member->value);

    app(RemoveMembership::class)->execute($member, $team);

    $stillMember = DB::table('model_has_roles')
        ->where('team_id', $team->id)
        ->where('model_id', $member->id)
        ->exists();

    expect($stillMember)->toBeFalse();
});

it('user soft-delete refuses when user owns a team, then succeeds after change owner', function (): void {
    $admin    = makeTeamOperator();
    $target   = User::factory()->createOne();
    $team     = Team::factory()->createOne(['owner_id' => $target->id]);
    $newOwner = User::factory()->createOne();

    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $target->assignRole(Role::Manager->value);
    $newOwner->assignRole(Role::Member->value);

    actingAs($admin);

    // UserResource refuses soft-delete while target owns this team
    Livewire::test(ListUsers::class)
        ->callTableAction('delete', $target);

    expect($target->fresh()->deleted_at)->toBeNull();

    // Transfer ownership
    app(ChangeTeamOwner::class)->execute($team, $newOwner, $admin);

    expect($team->fresh()->owner_id)->toBe($newOwner->id);

    // Now soft-delete succeeds
    Livewire::test(ListUsers::class)
        ->callTableAction('delete', $target);

    expect(User::withTrashed()->find($target->id)->deleted_at)->not->toBeNull();
});
