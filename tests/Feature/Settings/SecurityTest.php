<?php

declare(strict_types=1);

use App\Http\Middleware\SetCurrentTeam;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\withoutMiddleware;

beforeEach(fn () => withoutMiddleware(SetCurrentTeam::class));

test('security page is displayed', function () {
    skip_unless_fortify_has(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm'         => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->createOne();

    /** @mago-expect analysis:non-documented-method */
    actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'))
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('settings/Security')
                ->where('canManageTwoFactor', true)
                ->where('twoFactorEnabled', false),
        );
});

test('security page requires password confirmation when enabled', function () {
    skip_unless_fortify_has(Features::twoFactorAuthentication());

    $user = User::factory()->createOne();

    Features::twoFactorAuthentication([
        'confirm'         => true,
        'confirmPassword' => true,
    ]);

    $response = actingAs($user)->get(route('security.edit'));

    $response->assertRedirect(route('password.confirm'));
});

test('security page does not require password confirmation when disabled', function () {
    skip_unless_fortify_has(Features::twoFactorAuthentication());

    $user = User::factory()->createOne();

    Features::twoFactorAuthentication([
        'confirm'         => true,
        'confirmPassword' => false,
    ]);

    /** @mago-expect analysis:non-documented-method */
    actingAs($user)
        ->get(route('security.edit'))
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('settings/Security'),
        );
});

test('security page renders without two factor when feature is disabled', function () {
    skip_unless_fortify_has(Features::twoFactorAuthentication());

    config(['fortify.features' => []]);

    $user = User::factory()->createOne();

    /** @mago-expect analysis:non-documented-method */
    actingAs($user)
        ->get(route('security.edit'))
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('settings/Security')
                ->where('canManageTwoFactor', false)
                ->missing('twoFactorEnabled')
                ->missing('requiresConfirmation'),
        );
});

test('password can be updated', function () {
    $user = User::factory()->createOne();

    $response = actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password'      => 'password',
            'password'              => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('security.edit'));

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->createOne();

    $response = actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password'      => 'wrong-password',
            'password'              => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasErrors('current_password')
        ->assertRedirect(route('security.edit'));
});
