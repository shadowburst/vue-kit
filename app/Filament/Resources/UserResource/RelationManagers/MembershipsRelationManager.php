<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Actions\Membership\RemoveMembership;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MembershipsRelationManager extends RelationManager
{
    protected static string $relationship = 'teams';

    protected static ?string $title = 'Memberships';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('owner.name')
                    ->label('Owner'),
                TextColumn::make('members_count')
                    ->label('Members')
                    ->counts('members'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('removeFromTeam')
                    ->label('Remove from Team')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove from Team')
                    ->modalDescription('This will remove all of the user\'s roles in this team.')
                    ->action(function (Team $record): void {
                        /** @var User $user */
                        $user = $this->getOwnerRecord();

                        if ($record->owner_id === $user->id) {
                            Notification::make()
                                ->title('Cannot remove the team owner. Use the Change Owner action first.')
                                ->danger()
                                ->send();

                            return;
                        }

                        app(RemoveMembership::class)->execute($user, $record);
                        Notification::make()->title('User removed from team.')->success()->send();
                    }),
            ]);
    }
}
