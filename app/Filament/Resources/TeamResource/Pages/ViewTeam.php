<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamResource\Pages;

use App\Actions\Admin\ChangeTeamOwner;
use App\Filament\Resources\TeamResource;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewTeam extends ViewRecord
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('changeOwner')
                ->label('Change Owner')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Change Team Owner')
                ->modalDescription(
                    'The new owner will be assigned the manager role if they do not already hold it. '
                    .'The previous owner keeps their existing roles. '
                    .'This action bypasses the personal-team deletion restriction — '
                    .'if you then need to delete the previous owner\'s account, proceed from the User Resource.',
                )
                ->schema(function (): array {
                    /** @var Team $team */
                    $team = $this->getRecord();

                    $options = [];

                    foreach ($team->members()->pluck('name', 'id')->all() as $id => $name) {
                        if (is_string($name) && (is_int($id) || is_string($id))) {
                            $options[$id] = $name;
                        }
                    }

                    return [
                        Select::make('new_owner_id')
                            ->label('New Owner')
                            ->options($options)
                            ->required()
                            ->native(false),
                    ];
                })
                ->action(function (array $data): void {
                    /** @var Team $team */
                    $team = $this->getRecord();

                    if (! is_numeric($data['new_owner_id'] ?? null)) {
                        return;
                    }

                    /** @var User $newOwner */
                    $newOwner = User::query()->findOrFail((int) $data['new_owner_id']);

                    /** @var User $operator */
                    $operator = Auth::user();

                    app(ChangeTeamOwner::class)->execute($team, $newOwner, $operator);

                    Notification::make()->title('Team owner changed.')->success()->send();
                }),
        ];
    }
}
