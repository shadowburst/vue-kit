<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use App\Actions\Team\CreateTeam;
use App\Data\Auth\AuthRegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Fortify\Contracts\CreatesNewUsers;

final class CreateNewUser implements CreatesNewUsers
{
    public function __construct(
        private CreateTeam $createTeam,
    ) {}

    /**
     * @param  array<array-key, mixed>  $input
     */
    public function create(array $input): User
    {
        $data = AuthRegisterRequest::from($input);

        /** @var User */
        return DB::transaction(function () use ($data): User {
            /** @var User $user */
            $user = User::query()->create([
                'name'     => $data->name,
                'email'    => $data->email,
                'password' => $data->password,
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
