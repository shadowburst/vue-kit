<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\post;

it('prevents a soft-deleted user from authenticating via Fortify', function (): void {
    $user = User::factory()->createOne();
    $user->delete();

    post('/login', ['email' => $user->email, 'password' => 'password']);

    assertGuest();
});

it('restoring a soft-deleted user restores Fortify authentication', function (): void {
    $user = User::factory()->createOne();
    $user->delete();
    $user->restore();

    post('/login', ['email' => $user->email, 'password' => 'password']);

    assertAuthenticated();
});
