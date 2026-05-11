<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Data\Auth\AuthResetPasswordRequest;
use Illuminate\Foundation\Auth\User;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

final class ResetUserPassword implements ResetsUserPasswords
{
    /**
     * @param  array<array-key, mixed>  $input
     */
    public function reset(User $user, array $input): void
    {
        $data = AuthResetPasswordRequest::from($input);

        $user->forceFill([
            'password' => $data->password,
        ])->save();
    }
}
