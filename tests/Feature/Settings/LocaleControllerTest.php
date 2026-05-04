<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\put;

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
