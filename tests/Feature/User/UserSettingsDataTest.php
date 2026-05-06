<?php

declare(strict_types=1);

use App\Data\User\UserSettingsData;
use App\Enums\Settings\Locale;
use App\Models\User;

test('UserSettingsData round-trip cast stores and retrieves the correct locale', function (): void {
    $user = User::factory()->createOne([
        'settings' => new UserSettingsData(Locale::Fr),
    ]);

    $user->refresh();

    expect($user->settings)->toBeInstanceOf(UserSettingsData::class);
    expect($user->settings?->locale)->toBe(Locale::Fr);
});

test('UserSettingsData is null when settings column is null', function (): void {
    $user = User::factory()->createOne(['settings' => null]);

    $user->refresh();

    expect($user->settings)->toBeNull();
});
