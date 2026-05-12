<?php

declare(strict_types=1);

namespace App\Filament\Resources\TeamResource\RelationManagers;

use App\Actions\Membership\ChangeMembershipRole;
use App\Actions\Membership\RemoveMembership;
use App\Enums\Role\Role;
use App\Models\Team;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $title = 'Members';

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
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->state(function (User $record): string {
                        /** @var Team $team */
                        $team = $this->getOwnerRecord();

                        return (string) (DB::table('model_has_roles')
                            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                            ->where('model_has_roles.team_id', $team->id)
                            ->where('model_has_roles.model_id', $record->id)
                            ->where('model_has_roles.model_type', $record->getMorphClass())
                            ->value('roles.name') ?? 'unknown');
                    })
                    ->color(fn (string $state): string => match ($state) {
                        Role::Manager->value => 'warning',
                        default              => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('changeRole')
                    ->label('Change Role')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->schema([
                        Select::make('role')
                            ->label('New Role')
                            ->options([
                                Role::Manager->value => 'Manager',
                                Role::Member->value  => 'Member',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (User $record, array $data): void {
                        /** @var Team $team */
                        $team = $this->getOwnerRecord();

                        /** @var User $operator */
                        $operator = auth()->user();

                        app(ChangeMembershipRole::class)->execute($record, $team, Role::from($data['role']));

                        activity('admin')
                            ->causedBy($operator)
                            ->performedOn($team)
                            ->withProperties(['user_id' => $record->id, 'role' => $data['role']])
                            ->log('membership.update');

                        Notification::make()->title('Role updated.')->success()->send();
                    }),
                Action::make('removeMember')
                    ->label('Remove')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove Member')
                    ->modalDescription('This will remove all of the user\'s roles in this team.')
                    ->action(function (User $record): void {
                        /** @var Team $team */
                        $team = $this->getOwnerRecord();

                        if ($team->owner_id === $record->id) {
                            Notification::make()
                                ->title('Cannot remove team owner. Use the Change Owner action first.')
                                ->danger()
                                ->send();

                            return;
                        }

                        /** @var User $operator */
                        $operator = auth()->user();

                        app(RemoveMembership::class)->execute($record, $team);

                        activity('admin')
                            ->causedBy($operator)
                            ->performedOn($team)
                            ->withProperties(['user_id' => $record->id])
                            ->log('membership.update');

                        Notification::make()->title('Member removed.')->success()->send();
                    }),
            ]);
    }
}
