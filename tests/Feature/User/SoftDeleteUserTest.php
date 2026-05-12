<?php

declare(strict_types=1);

use App\Models\User;

it('prevents a soft-deleted user from authenticating via Fortify', function (): void {
    $user = User::factory()->createOne();
    $user->delete();

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $this->assertGuest();
});

it('restoring a soft-deleted user restores Fortify authentication', function (): void {
    $user = User::factory()->createOne();
    $user->delete();
    $user->restore();

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $this->assertAuthenticated();
});
