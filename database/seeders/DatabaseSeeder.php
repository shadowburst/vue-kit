<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => 'password'],
        );

        $adminTeam = $admin->ownedTeams()->firstOrCreate(['name' => 'Platform Admin']);
        $admin->update(['current_team_id' => $adminTeam->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($adminTeam->id);
        $admin->syncRoles([Role::Admin->value]);
    }
}
