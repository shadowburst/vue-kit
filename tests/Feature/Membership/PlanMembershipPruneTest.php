<?php

declare(strict_types=1);

use App\Actions\Membership\PlanMembershipPrune;
use App\Actions\Team\CreateTeam;
use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

function prune_assign(Team $team, Role $role, User $user): void
{
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $user->assignRole($role->value);
}

it('returns empty when the team is under cap', function (): void {
    $owner  = User::factory()->createOne();
    $team   = (new CreateTeam)->execute('Acme', $owner);
    $member = User::factory()->createOne();
    prune_assign($team, Role::Member, $member);

    expect((new PlanMembershipPrune)->execute($team, 3))->toBeEmpty();
});

it('returns empty when the team is exactly at cap', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Acme', $owner);

    foreach (range(1, 3) as $_) {
        prune_assign($team, Role::Member, User::factory()->createOne());
    }

    expect((new PlanMembershipPrune)->execute($team, 3))->toBeEmpty();
});

it('returns the correct excess members ordered by most-recently-added first', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Acme', $owner);

    $oldest  = User::factory()->createOne();
    $middle  = User::factory()->createOne();
    $newest1 = User::factory()->createOne();
    $newest2 = User::factory()->createOne();
    $newest3 = User::factory()->createOne();

    foreach ([[$oldest, -5], [$middle, -4], [$newest1, -3], [$newest2, -2], [$newest3, -1]] as [$user, $offset]) {
        prune_assign($team, Role::Member, $user);
        DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->where('team_id', $team->id)
            ->update(['created_at' => now()->addMinutes($offset)]);
    }

    $result = (new PlanMembershipPrune)->execute($team, 3); // 5 non-owners, cap=3 → remove 2

    expect($result)->toHaveCount(2);
    /** @mago-expect analysis:possibly-null-property-access */
    expect($result->first()->id)->toBe($newest3->id);
    /** @mago-expect analysis:possibly-null-property-access */
    expect($result->last()->id)->toBe($newest2->id);
});

it('returns all non-owner members when cap is zero', function (): void {
    $owner   = User::factory()->createOne();
    $team    = (new CreateTeam)->execute('Acme', $owner);
    $member1 = User::factory()->createOne();
    $member2 = User::factory()->createOne();
    $member3 = User::factory()->createOne();

    prune_assign($team, Role::Member, $member1);
    prune_assign($team, Role::Member, $member2);
    prune_assign($team, Role::Member, $member3);

    $result = (new PlanMembershipPrune)->execute($team, 0);

    expect($result)->toHaveCount(3);
    expect($result->pluck('id')->all())->toEqualCanonicalizing([$member1->id, $member2->id, $member3->id]);
});

it('excludes the team owner regardless of other roles present', function (): void {
    $owner  = User::factory()->createOne();
    $team   = (new CreateTeam)->execute('Acme', $owner);
    $admin  = User::factory()->createOne();
    $member = User::factory()->createOne();

    prune_assign($team, Role::Admin, $admin);
    prune_assign($team, Role::Member, $member);

    $result = (new PlanMembershipPrune)->execute($team, 0);

    expect($result)->toHaveCount(2);
    expect($result->pluck('id')->all())->toEqualCanonicalizing([$admin->id, $member->id]);
});

it('ordering by created_at desc is stable across members added in sequence', function (): void {
    $owner = User::factory()->createOne();
    $team  = (new CreateTeam)->execute('Acme', $owner);

    $first  = User::factory()->createOne();
    $second = User::factory()->createOne();
    $third  = User::factory()->createOne();

    prune_assign($team, Role::Admin, $first);
    prune_assign($team, Role::Member, $second);
    prune_assign($team, Role::Member, $third);

    DB::table('model_has_roles')
        ->where('model_id', $first->id)
        ->where('team_id', $team->id)
        ->update(['created_at' => now()->subSeconds(30)]);
    DB::table('model_has_roles')
        ->where('model_id', $second->id)
        ->where('team_id', $team->id)
        ->update(['created_at' => now()->subSeconds(20)]);
    DB::table('model_has_roles')
        ->where('model_id', $third->id)
        ->where('team_id', $team->id)
        ->update(['created_at' => now()->subSeconds(10)]);

    $result = (new PlanMembershipPrune)->execute($team, 1); // 3 non-owners, cap=1 → remove 2

    expect($result)->toHaveCount(2);
    expect($result->pluck('id')->all())->toEqual([$third->id, $second->id]);
});
