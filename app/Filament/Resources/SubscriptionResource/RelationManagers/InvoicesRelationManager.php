<?php

declare(strict_types=1);

namespace App\Filament\Resources\SubscriptionResource\RelationManagers;

use App\Models\Subscription;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Laravel\Cashier\Invoice;

class InvoicesRelationManager extends RelationManager
{
    // Dummy relationship name — table uses ->records() data source, not Eloquent.
    protected static string $relationship = 'owner';

    protected static ?string $title = 'Invoices';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (): Collection {
                /** @var Subscription $subscription */
                $subscription = $this->getOwnerRecord();

                try {
                    return collect($subscription->invoices())->map(fn (Invoice $invoice): array => [
                        '__key'  => $invoice->id,
                        'number' => $invoice->number ?? $invoice->id,
                        'total'  => $invoice->total(),
                        'status' => $invoice->asStripeInvoice()->status ?? '—',
                        'date'   => $invoice->date()?->toDateString() ?? '—',
                    ]);
                } catch (\Exception) {
                    return collect();
                }
            })
            ->columns([
                TextColumn::make('number')->label('Number'),
                TextColumn::make('total')->label('Total'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'paid'          => 'success',
                        'open'          => 'warning',
                        'void'          => 'gray',
                        'uncollectible' => 'danger',
                        default         => 'gray',
                    }),
                TextColumn::make('date')->label('Date'),
            ])
            ->recordUrl(null);
    }
}
