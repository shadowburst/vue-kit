<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Actions\Admin\GrantAdminRole;
use App\Actions\Admin\RevokeAdminRole;
use App\Enums\Role\Role;
use App\Enums\Settings\Locale;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\MembershipsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\OwnedTeamsRelationManager;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $modelLabel = 'User';

    protected static bool $shouldSkipAuthorization = true;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Profile')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(User::class, 'email', ignoreRecord: true)
                        ->maxLength(255),
                    TextInput::make('password')
                        ->password()
                        ->nullable()
                        ->dehydrated(fn ($state): bool => filled($state))
                        ->maxLength(255)
                        ->helperText('Leave blank to keep existing password.'),
                ])
                ->columns(2),
            Section::make('Settings')
                ->schema([
                    Select::make('settings.locale')
                        ->label('Locale')
                        ->options([
                            Locale::En->value => 'English',
                            Locale::Fr->value => 'French',
                        ])
                        ->native(false),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Profile')
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('email'),
                    TextEntry::make('email_verified_at')
                        ->label('Verified')
                        ->badge()
                        ->state(fn (User $record): string => $record->email_verified_at ? 'Verified' : 'Unverified')
                        ->color(fn (string $state): string => $state === 'Verified' ? 'success' : 'danger'),
                    TextEntry::make('roles_display')
                        ->label('Roles')
                        ->badge()
                        ->state(fn (User $record): array => static::getUserRoleNames($record))
                        ->color('warning'),
                    TextEntry::make('created_at')->dateTime(),
                    TextEntry::make('updated_at')->dateTime(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('verified')
                    ->label('Verified')
                    ->badge()
                    ->state(fn (User $record): string => $record->email_verified_at ? 'Verified' : 'Unverified')
                    ->color(fn (string $state): string => $state === 'Verified' ? 'success' : 'danger'),
                TextColumn::make('roles_display')
                    ->label('Roles')
                    ->badge()
                    ->state(fn (User $record): array => static::getUserRoleNames($record))
                    ->color('warning'),
                TextColumn::make('teams_count')
                    ->label('Memberships')
                    ->counts('teams')
                    ->sortable(),
                TextColumn::make('owned_teams_count')
                    ->label('Owned Teams')
                    ->counts('ownedTeams')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('grantAdmin')
                    ->label('Grant Admin')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => ! static::isAdmin($record) && $record->ownedTeams()->exists())
                    ->action(function (User $record): void {
                        try {
                            /** @var User $operator */
                            $operator = auth()->user();
                            app(GrantAdminRole::class)->execute($record, $operator);
                            Notification::make()->title('Admin role granted.')->success()->send();
                        } catch (RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('revokeAdmin')
                    ->label('Revoke Admin')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(function (User $record): bool {
                        /** @var User $operator */
                        $operator = auth()->user();

                        return static::isAdmin($record) && $operator->canRevokeAdminRole($record);
                    })
                    ->action(function (User $record): void {
                        /** @var User $operator */
                        $operator = auth()->user();
                        app(RevokeAdminRole::class)->execute($record, $operator);
                        Notification::make()->title('Admin role revoked.')->success()->send();
                    }),
                Action::make('impersonate')
                    ->label('Impersonate')
                    ->icon('heroicon-o-user-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => "Impersonate {$record->name}")
                    ->modalDescription('You will assume this user\'s identity. A banner will appear — use "Leave impersonation" to return.')
                    ->visible(fn (User $record): bool => ! static::isAdmin($record))
                    ->action(function (User $record): void {
                        /** @var User $operator */
                        $operator = auth()->user();

                        activity('admin')
                            ->causedBy($operator)
                            ->performedOn($record)
                            ->withProperties([
                                'ip' => request()->ip(),
                                'user_agent' => request()->userAgent(),
                            ])
                            ->log('impersonation.start');

                        $operator->impersonate($record);
                    })
                    ->successRedirectUrl(fn (): string => route('dashboard')),
                DeleteAction::make()
                    ->action(function (DeleteAction $action, User $record): void {
                        if ($record->ownedTeams()->withTrashed()->exists()) {
                            $count = $record->ownedTeams()->withTrashed()->count();
                            Notification::make()
                                ->title("Transfer ownership of {$count} team(s) in the Team Resource first.")
                                ->danger()
                                ->send();
                            $action->cancel();

                            return;
                        }

                        $record->delete();
                    }),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Force Delete User')
                    ->modalDescription('This permanently removes all user data and cannot be undone.')
                    ->action(function (User $record): void {
                        if ($record->ownedTeams()->withTrashed()->exists()) {
                            Notification::make()
                                ->title('Cannot force-delete: user still owns teams (including soft-deleted). Transfer or force-delete those teams first.')
                                ->danger()
                                ->send();

                            return;
                        }

                        if (static::hasMemberships($record)) {
                            Notification::make()
                                ->title('Cannot force-delete: remove all team memberships first.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->forceDelete();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRelations(): array
    {
        return [
            MembershipsRelationManager::class,
            OwnedTeamsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    /** @return array<string> */
    private static function getUserRoleNames(User $record): array
    {
        return DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $record->getKey())
            ->where('model_has_roles.model_type', $record->getMorphClass())
            ->distinct()
            ->pluck('roles.name')
            ->toArray();
    }

    private static function isAdmin(User $user): bool
    {
        return DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_id', $user->getKey())
            ->where('model_has_roles.model_type', $user->getMorphClass())
            ->where('roles.name', Role::Admin->value)
            ->where('roles.guard_name', 'web')
            ->exists();
    }

    public static function hasMemberships(User $user): bool
    {
        return DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))
            ->where('model_id', $user->getKey())
            ->where('model_type', $user->getMorphClass())
            ->exists();
    }
}
