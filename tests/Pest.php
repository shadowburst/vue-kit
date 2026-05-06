<?php

declare(strict_types=1);

use App\Actions\Membership\AssignMembership;
use App\Enums\Permission\Permission;
use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Fortify\Features;
use PHPUnit\Framework\Assert;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
 |--------------------------------------------------------------------------
 | Test Case
 |--------------------------------------------------------------------------
 |
 | The closure you provide to your test functions is always bound to a specific PHPUnit test
 | case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
 | need to change it using the "pest()" function to bind different classes or traits.
 |
 */

pest()
    ->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->beforeEach(function (): void {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::flushModelCache();
        Permission::flushModelCache();
        /**
         * @mago-expect analysis:undefined-variable
         * @mago-expect analysis:mixed-property-access
         */
        $this->seeder = RolePermissionSeeder::class;
    })
    ->in('Feature');

/*
 |--------------------------------------------------------------------------
 | Expectations
 |--------------------------------------------------------------------------
 |
 | When you're writing tests, you often need to check that values meet certain conditions. The
 | "expect()" function gives you access to a set of "expectations" methods that you can use
 | to assert different things. Of course, you may extend the Expectation API at any time.
 |
 */

/*
 |--------------------------------------------------------------------------
 | Functions
 |--------------------------------------------------------------------------
 |
 | While Pest is very powerful out-of-the-box, you may have some testing code specific to your
 | project that you don't want to repeat in every file. Here you can also expose helpers as
 | global functions to help you to reduce the number of lines of code in your test files.
 |
 */

function skip_unless_fortify_has(string $feature, ?string $message = null): void
{
    if (! Features::enabled($feature)) {
        Assert::markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
    }
}

/** @mago-expect analysis:mixed-return-statement */
function actingAsMemberOf(Team $team, Role $role): TestCase
{
    $user = User::factory()->createOne();
    (new AssignMembership)->execute($user, $team, $role);

    return test()->actingAs($user);
}
