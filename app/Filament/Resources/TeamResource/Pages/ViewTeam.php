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
                    . 'The previous owner keeps their existing roles. '
                    . 'This action bypasses the personal-team deletion restriction — '
                    . 'if you then need to delete the previous owner\'s account, proceed from the User Resource.'
                )
                ->schema(function (): array {
                    /** @var Team $team */
                    $team = $this->getRecord();

                    return [
                        Select::make('new_owner_id')
                            ->label('New Owner')
                            ->options($team->members()->pluck('name', 'id')->toArray())
                            ->required()
                            ->native(false),
                    ];
                })
                ->action(function (array $data): void {
                    /** @var Team $team */
                    $team = $this->getRecord();

                    /** @var User $newOwner */
                    $newOwner = User::findOrFail($data['new_owner_id']);

                    /** @var User $operator */
                    $operator = auth()->user();

                    app(ChangeTeamOwner::class)->execute($team, $newOwner, $operator);

                    Notification::make()->title('Team owner changed.')->success()->send();
                }),
        ];
    }
}
