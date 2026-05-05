<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Actions\Teams\CreateTeam;
use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

final class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        private CreateTeam $createTeam,
    ) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<array-key, mixed>  $input
     */
    public function create(array $input): User
    {
        $validated = Validator::make($input, [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
        ])->validate();

        /** @var User */
        return DB::transaction(function () use ($validated): User {
            $user = User::query()->create([
                'name'     => (string) $validated['name'],
                'email'    => (string) $validated['email'],
                'password' => (string) $validated['password'],
            ]);

            /** @var string $teamName */
            $teamName = __('team.app');
            $team     = $this->createTeam->execute($teamName, $user);

            $user->current_team_id = $team->id;
            $user->save();

            return $user;
        });
    }
}
