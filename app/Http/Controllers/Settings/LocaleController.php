<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Data\Locale\LocaleEditProps;
use App\Data\Locale\LocaleUpdateRequest;
use App\Data\User\UserSettingsData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class LocaleController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/Language', new LocaleEditProps);
    }

    public function update(LocaleUpdateRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $settings = $user->settings ?? new UserSettingsData;

        $settings->locale = $request->locale;

        $user->update([
            'settings' => $settings,
        ]);

        return back()->withCookie(cookie('locale', $settings->locale->value, 60 * 24 * 365));
    }
}
