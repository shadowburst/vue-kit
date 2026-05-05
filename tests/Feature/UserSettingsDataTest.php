<?php

declare(strict_types=1);

use App\Data\UserSettingsData;
use App\Enums\AppLocale;
use App\Models\User;

test('UserSettingsData round-trip cast stores and retrieves the correct locale', function (): void {
    $user = User::factory()->createOne([
        'settings' => new UserSettingsData(AppLocale::Fr),
    ]);

    $user->refresh();

    expect($user->settings)->toBeInstanceOf(UserSettingsData::class);
    expect($user->settings?->locale)->toBe(AppLocale::Fr);
});

test('UserSettingsData is null when settings column is null', function (): void {
    $user = User::factory()->createOne(['settings' => null]);

    $user->refresh();

    expect($user->settings)->toBeNull();
});
