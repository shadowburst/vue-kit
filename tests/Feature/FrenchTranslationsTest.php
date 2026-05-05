<?php

declare(strict_types=1);

use Illuminate\Support\Facades\App;

beforeEach(function (): void {
    App::setLocale('fr');
});

afterEach(function (): void {
    App::setLocale((string) config('app.locale'));
});

test('auth.failed returns French string when locale is fr', function (): void {
    expect(__('auth.failed'))->toBe('Ces identifiants ne correspondent pas à nos enregistrements.');
});

test('auth.password returns French string when locale is fr', function (): void {
    expect(__('auth.password'))->toBe('Le mot de passe fourni est incorrect.');
});

test('auth.throttle returns French string when locale is fr', function (): void {
    expect(__('auth.throttle'))->toContain('secondes');
});

test('validation.required returns French string when locale is fr', function (): void {
    expect(__('validation.required'))->toBe('Le champ :attribute est obligatoire.');
});

test('validation.email returns French string when locale is fr', function (): void {
    expect(__('validation.email'))->toBe('Le champ :attribute doit être une adresse e-mail valide.');
});

test('passwords.reset returns French string when locale is fr', function (): void {
    expect(__('passwords.reset'))->toBe('Votre mot de passe a été réinitialisé.');
});

test('pagination.previous returns French string when locale is fr', function (): void {
    expect(__('pagination.previous'))->toContain('Précédent');
});

test('pagination.next returns French string when locale is fr', function (): void {
    expect(__('pagination.next'))->toContain('Suivant');
});
