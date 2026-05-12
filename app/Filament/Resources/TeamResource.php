<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\Subscription\SubscriptionTier;
use App\Filament\Resources\TeamResource\Pages;
use App\Filament\Resources\TeamResource\RelationManagers\MembersRelationManager;
use App\Models\Team;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Teams';

    protected static ?string $modelLabel = 'Team';

    protected static bool $shouldSkipAuthorization = true;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Details')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Auto-generated from name.'),
                ])
                ->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Details')
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('slug'),
                    TextEntry::make('owner.name')->label('Owner'),
                    TextEntry::make('members_count')
                        ->label('Members')
                        ->state(fn (Team $record): int => (int) $record->members()->count()),
                    TextEntry::make('tier')
                        ->badge()
                        ->state(fn (Team $record): string => $record->tier->value)
                        ->color(fn (string $state): string => match ($state) {
                            SubscriptionTier::Pro->value => 'success',
                            default => 'gray',
                        }),
                    TextEntry::make('created_at')->dateTime(),
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
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->searchable(),
                TextColumn::make('members_count')
                    ->label('Members')
                    ->counts('members')
                    ->sortable(),
                TextColumn::make('tier')
                    ->badge()
                    ->state(fn (Team $record): string => $record->tier->value)
                    ->color(fn (string $state): string => match ($state) {
                        SubscriptionTier::Pro->value => 'success',
                        default => 'gray',
                    }),
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
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Force Delete Team')
                    ->modalDescription(
                        'This permanently removes all team data and cannot be undone. '
                        .'The team must be soft-deleted, have no remaining members, and no active subscription.',
                    )
                    ->action(function (Team $record): void {
                        if (static::hasBlockingMemberships($record)) {
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
                    }),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // Eager-load subscriptions so the tier accessor does not trigger lazy-loading violation in the table view.
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with('subscriptions');
    }

    public static function getRelations(): array
    {
        return [
            MembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'view'  => Pages\ViewTeam::route('/{record}'),
            'edit'  => Pages\EditTeam::route('/{record}/edit'),
        ];
    }

    public static function hasBlockingMemberships(Team $team): bool
    {
        return DB::table(Config::string('permission.table_names.model_has_roles', 'model_has_roles'))
            ->where('team_id', $team->getKey())
            ->where('model_type', (new User)->getMorphClass())
            ->where('model_id', '!=', $team->owner_id)
            ->exists();
    }
}
