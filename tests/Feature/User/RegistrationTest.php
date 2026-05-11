<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    skip_unless_fortify_has(Features::registration());
});

test('registration screen can be rendered with typed props', function () {
    $response = get(route('register'));

    $response->assertOk();

    /** @mago-expect analysis:non-documented-method */
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('auth/Register'),
    );
});

test('new users can register', function () {
    $response = post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
