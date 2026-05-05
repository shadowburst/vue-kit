<?php

declare(strict_types=1);

use App\Actions\Teams\CreateTeam;
use App\Enums\RoleName;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

it('creates a team with the given name', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $creator = User::factory()->create();

    $team = (new CreateTeam)->execute('Acme Corp', $creator);

    expect(Team::count())->toBe(1)
        ->and($team->name)->toBe('Acme Corp');
});

it('generates a slug from the team name', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $creator = User::factory()->create();

    $team = (new CreateTeam)->execute('Acme Corp', $creator);

    expect($team->slug)->toBe('acme-corp');
});

it('assigns the Owner role to the creator scoped to the new team', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $creator = User::factory()->create();

    $team = (new CreateTeam)->execute('Acme Corp', $creator);

    $hasOwnerRole = DB::table('model_has_roles')
        ->where('model_has_roles.model_id', $creator->id)
        ->where('model_has_roles.model_type', $creator->getMorphClass())
        ->where('model_has_roles.team_id', $team->id)
        ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->where('roles.name', RoleName::Owner->value)
        ->exists();

    expect($hasOwnerRole)->toBeTrue();
});

it('assigns only the Owner role and no other roles to the creator', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $creator = User::factory()->create();

    (new CreateTeam)->execute('Acme Corp', $creator);

    $roleCount = DB::table('model_has_roles')
        ->where('model_id', $creator->id)
        ->where('model_type', $creator->getMorphClass())
        ->count();

    expect($roleCount)->toBe(1);
});

it('does not change current_team_id when creator already had a current team', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $existingTeam = Team::query()->create(['name' => 'Existing Team']);
    $creator = User::factory()->create(['current_team_id' => $existingTeam->id]);

    (new CreateTeam)->execute('New Team', $creator);

    expect($creator->fresh()->current_team_id)->toBe($existingTeam->id);
});

it('does not change current_team_id when creator had no current team', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $creator = User::factory()->create(['current_team_id' => null]);

    (new CreateTeam)->execute('New Team', $creator);

    expect($creator->fresh()->current_team_id)->toBeNull();
});

it('rolls back team creation when role assignment fails', function (): void {
    // No roles seeded — assignRole throws RoleDoesNotExist, rolling back the transaction
    $creator = User::factory()->create();

    expect(fn () => (new CreateTeam)->execute('Acme Corp', $creator))
        ->toThrow(RoleDoesNotExist::class);

    expect(Team::count())->toBe(0);
});
