<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Admin\GrantAdminRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

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

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => 'password'],
        );
        if (! $admin instanceof User) {
            throw new RuntimeException('Unable to create admin user.');
        }

        $adminTeam = Team::query()->firstOrCreate([
            'owner_id' => $admin->id,
            'name'     => 'Platform Admin',
        ]);
        if (! $adminTeam instanceof Team) {
            throw new RuntimeException('Unable to create admin team.');
        }

        $admin->update(['current_team_id' => $adminTeam->id]);

        app(GrantAdminRole::class)->execute($admin);
    }
}
