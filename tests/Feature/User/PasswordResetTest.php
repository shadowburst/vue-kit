<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    skip_unless_fortify_has(Features::resetPasswords());
});

test('reset password link screen can be rendered with typed props', function () {
    $response = get(route('password.request'));

    $response->assertOk();

    /** @mago-expect analysis:non-documented-method */
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('auth/ForgotPassword')
            ->where('status', null),
    );
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->createOne();

    post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered with typed props', function () {
    Notification::fake();

    $user = User::factory()->createOne();

    post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $response = get(route('password.reset', $notification->token).'?email='.urlencode($user->email));

        $response->assertOk();

        /** @mago-expect analysis:non-documented-method */
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('auth/ResetPassword')
                ->has('token')
                ->has('email'),
        );

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->createOne();

    post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $response = post(route('password.update'), [
            'token'                 => $notification->token,
            'email'                 => $user->email,
            'password'              => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        return true;
    });
});

test('password cannot be reset with invalid token', function () {
    $user = User::factory()->createOne();

    $response = post(route('password.update'), [
        'token'                 => 'invalid-token',
        'email'                 => $user->email,
        'password'              => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasErrors('email');
});
