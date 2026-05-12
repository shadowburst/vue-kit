<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamResource\Pages;

use App\Actions\Admin\ChangeTeamOwner;
use App\Filament\Resources\TeamResource;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
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
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Force Delete Team')
                ->modalDescription(
                    'This permanently removes all team data and cannot be undone. '
                    .'The team must be soft-deleted, have no remaining members, and no active subscription.',
                )
                ->action(function (): void {
                    $record = $this->getRecord();

                    if (! $record instanceof Team) {
                        return;
                    }

                    if (TeamResource::hasBlockingMemberships($record)) {
                        Notification::make()
                            ->title('Cannot force-delete: remove all non-owner members first.')
                            ->danger()
                            ->send();

                        return;
                    }

                    if ($record->subscription('default')?->active() === true) {
                        Notification::make()
                            ->title('Cannot force-delete: cancel the active subscription first.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $record->forceDelete();
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }
}
