<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Data\Password\PasswordUpdateRequest;
use App\Data\Security\SecurityEditProps;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

final class SecurityController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return (
            Features::canManageTwoFactorAuthentication()
            && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')
                ? [new Middleware('password.confirm', only: ['edit'])]
                : []
        );
    }

    /**
     * Show the user's security settings page.
     */
    public function edit(Request $request, #[CurrentUser] User $user): Response
    {
        $canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($canManageTwoFactor) {
            $this->ensureTwoFactorStateIsValid($request, $user);
        }

        return Inertia::render('settings/Security', new SecurityEditProps(
            canManageTwoFactor  : $canManageTwoFactor,
            twoFactorEnabled    : $canManageTwoFactor && $user->hasEnabledTwoFactorAuthentication(),
            requiresConfirmation: $canManageTwoFactor
            && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
        ));
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $user->update([
            'password' => $request->password,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated.')]);

        return back();
    }

    // Inlines Fortify's InteractsWithTwoFactorState::ensureStateIsValid() so we can use
    // plain Request + User args instead of the FormRequest mixin (ADR-0017 D6).
    private function ensureTwoFactorStateIsValid(Request $request, User $user): void
    {
        if (! Fortify::confirmsTwoFactorAuthentication()) {
            return;
        }

        $currentTime = time();

        if (! $user->hasEnabledTwoFactorAuthentication()) {
            $request->session()->put('two_factor_empty_at', $currentTime);
        }

        $hasJustBegunConfirming =
            ! is_null($user->two_factor_secret)
            && is_null($user->two_factor_confirmed_at)
            && $request->session()->has('two_factor_empty_at')
            && is_null($request->session()->get('two_factor_confirming_at'));

        if ($hasJustBegunConfirming) {
            $request->session()->put('two_factor_confirming_at', $currentTime);
        }

        $neverFinishedConfirming =
            ! $request->hasOldInput('code')
            && is_null($user->two_factor_confirmed_at)
            && $request->session()->get('two_factor_confirming_at', 0) !== $currentTime;

        if ($neverFinishedConfirming) {
            app(DisableTwoFactorAuthentication::class)($user);
            $request->session()->put('two_factor_empty_at', $currentTime);
            $request->session()->remove('two_factor_confirming_at');
        }
    }
}
