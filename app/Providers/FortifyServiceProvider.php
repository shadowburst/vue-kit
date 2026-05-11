<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Data\Auth\AuthConfirmPasswordProps;
use App\Data\Auth\AuthConfirmPasswordRequest;
use App\Data\Auth\AuthForgotPasswordProps;
use App\Data\Auth\AuthLoginProps;
use App\Data\Auth\AuthLoginRequest;
use App\Data\Auth\AuthRegisterProps;
use App\Data\Auth\AuthResetPasswordProps;
use App\Data\Auth\AuthTwoFactorChallengeProps;
use App\Data\Auth\AuthVerifyEmailProps;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    private function configureActions(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::authenticateUsing(function (Request $request) {
            $data = AuthLoginRequest::from($request);

            $user = User::where('email', $data->email)->first();

            if ($user && Hash::check($data->password, $user->password)) {
                return $user;
            }

            return null;
        });

        Fortify::confirmPasswordsUsing(function ($user, $password) {
            AuthConfirmPasswordRequest::from(request());

            return Hash::check($password, $user->password);
        });
    }

    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', new AuthLoginProps(
            canResetPassword: Features::enabled(Features::resetPasswords()),
            canRegister: Features::enabled(Features::registration()),
            status: $request->session()->get('status'),
        )));

        Fortify::registerView(fn () => Inertia::render('auth/Register', new AuthRegisterProps));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', new AuthForgotPasswordProps(
            status: $request->session()->get('status'),
        )));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', new AuthResetPasswordProps(
            token: (string) $request->route('token'),
            email: (string) $request->string('email'),
        )));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', new AuthVerifyEmailProps(
            status: $request->session()->get('status'),
        )));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword', new AuthConfirmPasswordProps));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge', new AuthTwoFactorChallengeProps));
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get(
            'login.id',
        )));

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower((string) $request->input(Fortify::username())).'|'.(string) $request->ip(),
            );

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
