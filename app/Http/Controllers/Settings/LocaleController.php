<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Data\User\UserSettingsData;
use App\Enums\Settings\Locale;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\LocaleStoreRequest;
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

    public function update(LocaleUpdateRequest $request): RedirectResponse
    {
        $locale = (string) $request->validated('locale');

        return back()->withCookie(cookie('locale', $locale, 60 * 24 * 365));
    }

    public function store(LocaleStoreRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $locale = (string) $request->validated('locale');

        $user->update([
            'settings' => new UserSettingsData(Locale::from($locale)),
        ]);

        return back()->withCookie(cookie('locale', $locale, 60 * 24 * 365));
    }
}
