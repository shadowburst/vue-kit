<?php

declare(strict_types=1);

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Actions\Admin\CancelSubscription;
use App\Filament\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openInStripe')
                ->label('Open in Stripe')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(function (): ?string {
                    $subscription = $this->getRecord();

                    if (! $subscription instanceof Subscription) {
                        return null;
                    }

                    $team = $subscription->owner;

                    if (! $team instanceof Team) {
                        return null;
                    }

                    $stripeId = $team->stripe_id;

                    return $stripeId !== null
                        ? "https://dashboard.stripe.com/customers/{$stripeId}"
                        : null;
                })
                ->openUrlInNewTab()
                ->visible(function (): bool {
                    $subscription = $this->getRecord();

                    if (! $subscription instanceof Subscription) {
                        return false;
                    }

                    $team = $subscription->owner;

                    return $team instanceof Team && $team->stripe_id !== null;
                }),
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
                ->visible(function (): bool {
                    $subscription = $this->getRecord();

                    if (! $subscription instanceof Subscription) {
                        return false;
                    }

                    return $subscription->active() && ! $subscription->canceled();
                })
                ->action(function (): void {
                    $subscription = $this->getRecord();

                    if (! $subscription instanceof Subscription) {
                        return;
                    }

                    /** @var User $operator */
                    $operator = Auth::user();

                    try {
                        app(CancelSubscription::class)->execute($subscription, $operator);
                        Notification::make()->title('Subscription cancelled at period end.')->success()->send();
                        $this->refreshFormData(['stripe_status', 'ends_at']);
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
                ->visible(function (): bool {
                    $subscription = $this->getRecord();

                    if (! $subscription instanceof Subscription) {
                        return false;
                    }

                    return $subscription->onGracePeriod();
                })
                ->action(function (): void {
                    $subscription = $this->getRecord();

                    if (! $subscription instanceof Subscription) {
                        return;
                    }

                    /** @var User $operator */
                    $operator = Auth::user();

                    $team = $subscription->owner;

                    if (! $team instanceof Team) {
                        return;
                    }

                    $subscription->resume();

                    activity('admin')
                        ->causedBy($operator)
                        ->performedOn($subscription)
                        ->withProperties(['team_id' => $team->id])
                        ->log('subscription.resume');

                    Notification::make()->title('Subscription resumed.')->success()->send();
                    $this->refreshFormData(['stripe_status', 'ends_at']);
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
                ->visible(function (): bool {
                    $subscription = $this->getRecord();

                    if (! $subscription instanceof Subscription) {
                        return false;
                    }

                    return $subscription->onTrial();
                })
                ->action(function (array $data): void {
                    $subscription = $this->getRecord();

                    if (! $subscription instanceof Subscription) {
                        return;
                    }

                    /** @var User $operator */
                    $operator = Auth::user();

                    if (! is_string($data['trial_ends_at'] ?? null)) {
                        return;
                    }

                    $team = $subscription->owner;

                    if (! $team instanceof Team) {
                        return;
                    }

                    $oldDate = $subscription->trial_ends_at?->toDateString();
                    $newDate = Carbon::parse($data['trial_ends_at']);

                    $subscription->extendTrial($newDate);

                    activity('admin')
                        ->causedBy($operator)
                        ->performedOn($subscription)
                        ->withProperties([
                            'team_id'  => $team->id,
                            'old_date' => $oldDate,
                            'new_date' => $newDate->toDateString(),
                        ])
                        ->log('subscription.trial.extend');

                    Notification::make()->title('Trial extended.')->success()->send();
                    $this->refreshFormData(['trial_ends_at']);
                }),
        ];
    }
}
