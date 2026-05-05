<?php

declare(strict_types=1);

use Database\Seeders\RolePermissionSeeder;
use Laravel\Fortify\Features;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\seed;

beforeEach(function () {
    skip_unless_fortify_has(Features::registration());
    seed(RolePermissionSeeder::class);
});

test('registration screen can be rendered', function () {
    $response = get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
