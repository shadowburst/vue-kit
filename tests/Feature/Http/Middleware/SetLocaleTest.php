<?php

declare(strict_types=1);

use App\Data\UserSettingsData;
use App\Enums\AppLocale;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Route::get('/_test/locale', fn () => response()->json(['locale' => app()->getLocale()]))->middleware('web');
});

test('cookie branch sets locale from cookie', function (): void {
    $response = $this->withUnencryptedCookies(['locale' => 'fr'])
        ->get('/_test/locale');

    $response->assertOk();
    expect($response->json('locale'))->toBe('fr');
});

test('accept-language branch sets locale from header', function (): void {
    $response = $this->get('/_test/locale', ['Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.8']);

    $response->assertOk();
    expect($response->json('locale'))->toBe('fr');
});

test('accept-language with exact locale value sets locale', function (): void {
    $response = $this->get('/_test/locale', ['Accept-Language' => 'fr']);

    $response->assertOk();
    expect($response->json('locale'))->toBe('fr');
});

test('fallback branch uses configured fallback locale', function (): void {
    $response = $this->get('/_test/locale');

    $response->assertOk();
    expect($response->json('locale'))->toBe(config('app.fallback_locale'));
});

test('unsupported locale in cookie falls through to accept-language', function (): void {
    $response = $this->withUnencryptedCookies(['locale' => 'de'])
        ->get('/_test/locale', ['Accept-Language' => 'fr']);

    $response->assertOk();
    expect($response->json('locale'))->toBe('fr');
});

test('unsupported locale in cookie falls through to fallback when no accept-language', function (): void {
    $response = $this->withUnencryptedCookies(['locale' => 'de'])
        ->get('/_test/locale');

    $response->assertOk();
    expect($response->json('locale'))->toBe(config('app.fallback_locale'));
});

test('unsupported locale in accept-language falls through to fallback', function (): void {
    $response = $this->get('/_test/locale', ['Accept-Language' => 'de,zh;q=0.9']);

    $response->assertOk();
    expect($response->json('locale'))->toBe(config('app.fallback_locale'));
});

test('cookie takes priority over accept-language', function (): void {
    $response = $this->withUnencryptedCookies(['locale' => 'en'])
        ->get('/_test/locale', ['Accept-Language' => 'fr']);

    $response->assertOk();
    expect($response->json('locale'))->toBe('en');
});

test('middleware does not throw on malformed accept-language header', function (): void {
    $response = $this->get('/_test/locale', ['Accept-Language' => ';;;invalid;;;']);

    $response->assertOk();
    expect($response->json('locale'))->toBe(config('app.fallback_locale'));
});

test('authenticated user stored locale takes priority over cookie', function (): void {
    $user = User::factory()->createOne([
        'settings' => new UserSettingsData(AppLocale::Fr),
    ]);

    $response = actingAs($user)
        ->withUnencryptedCookies(['locale' => 'en'])
        ->get('/_test/locale');

    $response->assertOk();
    expect($response->json('locale'))->toBe('fr');
});
