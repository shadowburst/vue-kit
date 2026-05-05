<?php

declare(strict_types=1);

use App\Http\Middleware\SetCurrentTeam;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withoutMiddleware;

beforeEach(fn () => withoutMiddleware(SetCurrentTeam::class));

test('confirm password screen can be rendered', function () {
    $user = User::factory()->createOne();

    $response = actingAs($user)->get(route('password.confirm'));

    $response->assertOk();

    /** @mago-expect analysis:non-documented-method */
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('auth/ConfirmPassword'),
    );
});

test('password confirmation requires authentication', function () {
    $response = get(route('password.confirm'));

    $response->assertRedirect(route('login'));
});
