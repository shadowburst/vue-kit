<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Data\User\UserSettingsData;
use App\Enums\Settings\Locale;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LocaleUpdateRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class LocaleController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/Language');
    }

    public function update(LocaleUpdateRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $request->validated();

        $settings = $user->settings ?? new UserSettingsData;

        $settings->locale = $request->enum('locale', Locale::class, Locale::Fr);

        $user->update([
            'settings' => $settings,
        ]);

        return back()->withCookie(cookie('locale', $settings->locale->value, 60 * 24 * 365));
    }
}
