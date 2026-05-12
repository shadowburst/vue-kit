<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Actions\Admin\CancelSubscription;
use App\Enums\Subscription\SubscriptionTier;
use App\Filament\Resources\SubscriptionResource\Pages;
use App\Filament\Resources\SubscriptionResource\RelationManagers\InvoicesRelationManager;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Subscriptions';

    protected static ?string $modelLabel = 'Subscription';

    protected static bool $shouldSkipAuthorization = true;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Subscription')
                ->schema([
                    TextEntry::make('owner.name')->label('Team'),
                    TextEntry::make('owner.owner.name')->label('Team Owner'),
                    TextEntry::make('tier')
                        ->label('Tier')
                        ->badge()
                        ->state(fn (Subscription $record): string => self::resolveTier($record)->value)
                        ->color(fn (string $state): string => match ($state) {
                            SubscriptionTier::Pro->value => 'success',
                            default => 'gray',
                        }),
                    TextEntry::make('stripe_status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            'active'   => 'success',
                            'trialing' => 'info',
                            'canceled' => 'danger',
                            'past_due' => 'warning',
                            default    => 'gray',
                        }),
                    TextEntry::make('stripe_price')->label('Plan')->default('—'),
                    TextEntry::make('stripe_id')->label('Stripe ID'),
                    TextEntry::make('trial_ends_at')->label('Trial Ends')->dateTime()->placeholder('—'),
                    TextEntry::make('ends_at')->label('Ends At')->dateTime()->placeholder('—'),
                    TextEntry::make('created_at')->label('Created')->dateTime(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('owner.name')
                    ->label('Team')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Subscription $record): ?string => TeamResource::getUrl('view', [
                        'record' => $record->owner,
                    ])),
                TextColumn::make('tier')
                    ->label('Tier')
                    ->badge()
                    ->state(fn (Subscription $record): string => self::resolveTier($record)->value)
                    ->color(fn (string $state): string => match ($state) {
                        SubscriptionTier::Pro->value => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('stripe_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'active'   => 'success',
                        'trialing' => 'info',
                        'canceled' => 'danger',
                        'past_due' => 'warning',
                        default    => 'gray',
                    }),
                TextColumn::make('stripe_price')
                    ->label('Plan')
                    ->default('—'),
                TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ends_at')
                    ->label('Ends At')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                Action::make('cancelAtPeriodEnd')
                    ->label('Cancel at Period End')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Subscription at Period End')
                    ->modalDescription(
                        'The subscription will remain active until the end of the current billing period. '
                        .'This will be refused if the team has members that exceed the Free tier cap.',
                    )
                    ->visible(fn (Subscription $record): bool => $record->active() && ! $record->canceled())
                    ->action(function (Subscription $record): void {
                        /** @var User $operator */
                        $operator = Auth::user();

                        try {
                            app(CancelSubscription::class)->execute($record, $operator);
                            Notification::make()->title('Subscription cancelled at period end.')->success()->send();
                        } catch (RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('resume')
                    ->label('Resume')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Resume Subscription')
                    ->visible(fn (Subscription $record): bool => $record->onGracePeriod())
                    ->action(function (Subscription $record): void {
                        /** @var User $operator */
                        $operator = Auth::user();

                        $team = $record->owner;
                        /** @var Team $team */

                        $record->resume();

                        activity('admin')
                            ->causedBy($operator)
                            ->performedOn($record)
                            ->withProperties(['team_id' => $team->id])
                            ->log('subscription.resume');

                        Notification::make()->title('Subscription resumed.')->success()->send();
                    }),
                Action::make('extendTrial')
                    ->label('Extend Trial')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->schema([
                        DatePicker::make('trial_ends_at')
                            ->label('New Trial End Date')
                            ->minDate(now()->addDay())
                            ->required(),
                    ])
                    ->visible(fn (Subscription $record): bool => $record->onTrial())
                    ->action(function (Subscription $record, array $data): void {
                        /** @var User $operator */
                        $operator = Auth::user();

                        if (! is_string($data['trial_ends_at'] ?? null)) {
                            return;
                        }

                        $team = $record->owner;
                        /** @var Team $team */

                        $oldDate = $record->trial_ends_at?->toDateString();
                        $newDate = Carbon::parse($data['trial_ends_at']);

                        $record->extendTrial($newDate);

                        activity('admin')
                            ->causedBy($operator)
                            ->performedOn($record)
                            ->withProperties([
                                'team_id'  => $team->id,
                                'old_date' => $oldDate,
                                'new_date' => $newDate->toDateString(),
                            ])
                            ->log('subscription.trial.extend');

                        Notification::make()->title('Trial extended.')->success()->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            InvoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'view'  => Pages\ViewSubscription::route('/{record}'),
        ];
    }

    private static function resolveTier(Subscription $record): SubscriptionTier
    {
        return SubscriptionTier::fromStripePriceId((string) ($record->stripe_price ?? ''));
    }
}
