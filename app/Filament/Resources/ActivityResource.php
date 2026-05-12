<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages;
use BackedEnum;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity as ActivityModel;

class ActivityResource extends Resource
{
    protected static ?string $model = ActivityModel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Audit Log';

    protected static ?string $modelLabel = 'Activity';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('log_name', 'admin');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('log_name')->label('Log'),
            TextEntry::make('description')->label('Event'),
            TextEntry::make('causer.name')->label('Operator')->default('system'),
            TextEntry::make('subject_type')->label('Subject Type'),
            TextEntry::make('subject_id')->label('Subject ID'),
            TextEntry::make('created_at')->label('Date')->dateTime(),
            KeyValueEntry::make('properties')->label('Properties'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('causer.name')
                    ->label('Operator')
                    ->default('system'),
                TextColumn::make('description')
                    ->label('Event')
                    ->searchable(),
                TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn (?string $state, ActivityModel $record): string => $state
                        ? class_basename($state).' #'.$record->subject_id
                        : '—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'view'  => Pages\ViewActivity::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
