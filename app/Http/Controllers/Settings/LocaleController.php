<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Data\UserSettingsData;
use App\Enums\AppLocale;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

final class LocaleController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/Language');
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var array{locale: string} $validated */
        $validated = $request->validate([
            'locale' => ['required', 'string', new Enum(AppLocale::class)],
        ]);

        return back()->withCookie(cookie('locale', $validated['locale'], 60 * 24 * 365));
    }

    public function store(Request $request, #[CurrentUser] User $user): RedirectResponse
    {
        /** @var array{locale: string} $validated */
        $validated = $request->validate([
            'locale' => ['required', 'string', new Enum(AppLocale::class)],
        ]);

        $user->update([
            'settings' => new UserSettingsData(AppLocale::from($validated['locale'])),
        ]);

        return back()->withCookie(cookie('locale', $validated['locale'], 60 * 24 * 365));
    }
}
