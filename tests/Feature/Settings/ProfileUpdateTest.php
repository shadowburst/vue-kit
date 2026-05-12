<?php

declare(strict_types=1);

use App\Http\Middleware\Team\SetCurrentTeam;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\withoutMiddleware;

beforeEach(fn () => withoutMiddleware(SetCurrentTeam::class));

test('profile page is displayed', function () {
    $user = User::factory()->createOne();

    actingAs($user)
        ->get(route('profile.edit'))
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('settings/Profile')
                ->has('mustVerifyEmail')
                ->where('mustVerifyEmail', false)
                ->has('status')
                ->where('status', null),
        );
});

test('profile information can be updated', function () {
    $user = User::factory()->createOne();

    $response = actingAs($user)
        ->patch(route('profile.update'), [
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->createOne();

    $response = actingAs($user)
        ->patch(route('profile.update'), [
            'name'  => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    /** @mago-expect analysis:non-documented-method */
    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->createOne();

    $response = actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    assertGuest();
    expect($user->fresh())->toBeNull();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->createOne();

    $response = actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    /** @mago-expect analysis:non-documented-method */
    expect($user->fresh())->not->toBeNull();
});
