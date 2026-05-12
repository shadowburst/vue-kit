<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\Settings\Locale;
use App\Filament\Resources\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /** @param array<string, mixed> $data */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record             = $this->getRecord();
        $data['settings']   = [
            'locale' => $record->settings?->locale->value ?? Locale::Fr->value,
        ];

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->action(function (DeleteAction $action): void {
                    $record = $this->getRecord();
                    $count = UserResource::ownedTeamsCountIncludingTrashed($record);

                    if ($count > 0) {
                        Notification::make()
                            ->title("Transfer ownership of {$count} team(s) in the Team Resource first.")
                            ->danger()
                            ->send();
                        $action->cancel();

                        return;
                    }

                    $record->delete();
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
            RestoreAction::make(),
            ForceDeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Force Delete User')
                ->modalDescription('This permanently removes all user data and cannot be undone.')
                ->action(function (): void {
                    $record = $this->getRecord();

                    if ($record->ownedTeams()->withTrashed()->exists()) {
                        Notification::make()
                            ->title('Cannot force-delete: user still owns teams (including soft-deleted).')
                            ->danger()
                            ->send();

                        return;
                    }

                    if (UserResource::hasMemberships($record)) {
                        Notification::make()
                            ->title('Cannot force-delete: remove all team memberships first.')
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
