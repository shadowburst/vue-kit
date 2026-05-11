<?php

declare(strict_types=1);

use App\Http\Middleware\Team\SetCurrentTeam;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withoutMiddleware;

test('guests are redirected to the login page', function () {
    $response = get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    withoutMiddleware(SetCurrentTeam::class);
    $user = User::factory()->createOne();

    actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
        );
});
