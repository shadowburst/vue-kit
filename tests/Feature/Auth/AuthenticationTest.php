<?php

declare(strict_types=1);

use App\Http\Middleware\SetCurrentTeam;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(fn () => $this->withoutMiddleware(SetCurrentTeam::class));

test('login screen can be rendered', function () {
    $response = get(route('login'));

    $response->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->createOne();

    $response = post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    skip_unless_fortify_has(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm'         => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->createOne();

    $user->forceFill([
        'two_factor_secret'         => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at'   => now(),
    ])->save();

    $response = post(route('login'), [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->createOne();

    post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'wrong-password',
    ]);

    assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->createOne();

    $response = actingAs($user)->post(route('logout'));

    assertGuest();
    $response->assertRedirect(route('home'));
});

test('users are rate limited', function () {
    $user = User::factory()->createOne();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
