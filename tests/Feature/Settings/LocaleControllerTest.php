<?php

declare(strict_types=1);

use App\Enums\AppLocale;
use App\Http\Middleware\SetCurrentTeam;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\put;

beforeEach(fn () => $this->withoutMiddleware(SetCurrentTeam::class));

test('language settings page is displayed for authenticated users', function (): void {
    $user = User::factory()->createOne();

    $response = actingAs($user)->get(route('locale.edit'));

    $response->assertOk();
});

test('valid locale sets the cookie and redirects back', function (): void {
    $response = put(route('locale.update'), ['locale' => 'fr']);

    $response->assertRedirect();
    $response->assertPlainCookie('locale', 'fr');
});

test('valid locale does not require authentication', function (): void {
    $response = put(route('locale.update'), ['locale' => 'en']);

    $response->assertRedirect();
    $response->assertPlainCookie('locale', 'en');
});

test('invalid locale returns a validation error on the locale field', function (): void {
    $response = put(route('locale.update'), ['locale' => 'de']);

    $response->assertSessionHasErrors('locale');
});

test('missing locale returns a validation error', function (): void {
    $response = put(route('locale.update'), []);

    $response->assertSessionHasErrors('locale');
});

test('authenticated patch updates user settings locale and sets cookie', function (): void {
    $user = User::factory()->createOne();

    $response = actingAs($user)->patch(route('locale.store'), ['locale' => 'fr']);

    $response->assertRedirect();
    $response->assertPlainCookie('locale', 'fr');

    $user->refresh();
    expect($user->settings?->locale)->toBe(AppLocale::Fr);
});

test('authenticated patch rejects unsupported locale with validation error', function (): void {
    $user = User::factory()->createOne();

    $response = actingAs($user)->patch(route('locale.store'), ['locale' => 'de']);

    $response->assertSessionHasErrors('locale');
});
