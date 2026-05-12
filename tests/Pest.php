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

function skipUnlessFortifyHas(string $feature, ?string $message = null): void
{
    if (! Features::enabled($feature)) {
        Assert::markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
    }
}

function assignRoleInTeam(User $user, Team $team, Role $role): void
{
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
    $user->assignRole($role->value);
}

/** @mago-expect analysis:mixed-return-statement */
function actingAsMemberOf(Team $team, Role $role): TestCase
{
    $user = User::factory()->createOne();
    (new AssignMembership)->execute($user, $team, $role);
    $user->update(['current_team_id' => $team->id]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);

    return test()->actingAs($user);
}

/**
 * @return list<class-string>
 */
function discoverPhpClasses(string $directory, string $namespace, string $filePattern = '*.php'): array
{
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

    return collect(iterator_to_array($iterator))
        ->filter(fn (SplFileInfo $file): bool => $file->isFile() && fnmatch($filePattern, $file->getFilename()))
        ->map(function (SplFileInfo $file) use ($directory, $namespace): string {
            $relativePath  = str_replace($directory.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relativeClass = str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relativePath);

            return $namespace.'\\'.$relativeClass;
        })
        ->sort()
        ->values()
        ->all();
}

expect()->extend('toHaveCorrespondingResourceIn', function (string $resourceNamespace) {
    $resourceClass = $resourceNamespace.'\\'.class_basename($this->value).'Resource';

    Assert::assertTrue(
        class_exists($resourceClass),
        "Expected model [{$this->value}] to have corresponding resource [{$resourceClass}].",
    );

    return $this;
});
