<?php

declare(strict_types=1);

use App\Enums\Settings\Locale;
use App\Http\Middleware\Team\SetCurrentTeam;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;
use function Pest\Laravel\withoutMiddleware;

beforeEach(fn () => withoutMiddleware(SetCurrentTeam::class));

test('language settings page is displayed for authenticated users', function (): void {
    $user = User::factory()->createOne();

    $response = actingAs($user)->get(route('locale.edit'));

    $response->assertOk();
});

test('valid locale updates user settings, sets cookie and redirects back', function (): void {
    $user = User::factory()->createOne();

    $response = actingAs($user)->patch(route('locale.update'), ['locale' => 'fr']);

    $response->assertRedirect();
    $response->assertPlainCookie('locale', 'fr');

    $user->refresh();
    expect($user->settings?->locale)->toBe(Locale::Fr);
});

test('invalid locale returns a validation error on the locale field', function (): void {
    $user = User::factory()->createOne();

    $response = actingAs($user)->patch(route('locale.update'), ['locale' => 'de']);

    $response->assertSessionHasErrors('locale');
});

test('missing locale returns a validation error', function (): void {
    $user = User::factory()->createOne();

    $response = actingAs($user)->patch(route('locale.update'), []);

    $response->assertSessionHasErrors('locale');
});

test('unauthenticated request to update locale is redirected to login', function (): void {
    $response = patch(route('locale.update'), ['locale' => 'fr']);

    $response->assertRedirect(route('login'));
});
