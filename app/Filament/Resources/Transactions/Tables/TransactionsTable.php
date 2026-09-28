<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Helpers\FormatCurrency;
use App\Models\Transaction;
use Filament\Actions\{Action, DeleteAction, ViewAction};
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\{SelectFilter, TernaryFilter};
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            // Agregados das parcelas numa query só (sem N+1).
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with('proposal.customer')
                ->withCount(['payments as paid_count' => fn (Builder $query) => $query->whereNotNull('paid_at')])
                ->withSum(['payments as paid_sum' => fn (Builder $query) => $query->whereNotNull('paid_at')], 'amount')
                ->withMin(['payments as next_due_date' => fn (Builder $query) => $query->whereNull('paid_at')], 'due_date'))
            ->columns([
                TextColumn::make('proposal.customer.name')
                    ->label('Cliente')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->state(fn (Transaction $record): string => $record->isSubscription() ? 'Assinatura' : 'Orçamento Fechado')
                    ->color(fn (Transaction $record): string => $record->isSubscription() ? 'info' : 'gray'),
                TextColumn::make('amount')
                    ->label('Valor')
                    ->sortable()
                    ->formatStateUsing(fn (Transaction $record): string => FormatCurrency::getFormatCurrency($record->amount)
                        . ($record->isSubscription() ? '/mês' : '')),
                TextColumn::make('paid_sum')
                    ->label('Recebido')
                    ->formatStateUsing(fn (?string $state): string => FormatCurrency::getFormatCurrency($state ?? 0))
                    ->placeholder(FormatCurrency::getFormatCurrency(0)),
                TextColumn::make('paid_count')
                    ->label('Parcelas')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn (Transaction $record): string => $record->isSubscription()
                        ? $record->paid_count . ' ' . ($record->paid_count === 1 ? 'paga' : 'pagas')
                        : $record->paid_count . '/' . $record->installments),
                TextColumn::make('next_due_date')
                    ->label('Próx. vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—')
                    ->color(fn (?string $state): ?string => $state && Carbon::parse($state)->lt(today()) ? 'danger' : null),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (Transaction $record): string => $record->statusLabel())
                    ->color(fn (Transaction $record): string => $record->statusColor()),
                TextColumn::make('created_at')
                    ->label('Criada em')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        Transaction::PENDING => 'Em aberto / ativa',
                        Transaction::PAID => 'Quitada',
                        Transaction::CANCELED => 'Cancelada',
                    ]),
                TernaryFilter::make('subscription')
                    ->label('Tipo')
                    ->placeholder('Todos')
                    ->trueLabel('Assinatura')
                    ->falseLabel('Orçamento Fechado')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNull('installments'),
                        false: fn (Builder $query): Builder => $query->whereNotNull('installments'),
                    ),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Parcelas'),
                static::cancelAction()
                    ->iconButton(),
                DeleteAction::make()
                    ->iconButton(),
            ]);
    }

    /** Cancela a cobrança (cliente desistiu). Parcelas pagas continuam como recebidas. */
    public static function cancelAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancelar cobrança')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('As parcelas já pagas continuam contando como recebidas; as em aberto deixam de ser cobradas.')
            ->visible(fn (Transaction $record): bool => ! $record->isCanceled())
            ->action(function (Transaction $record) {
                $record->update(['status' => Transaction::CANCELED]);

                Notification::make()->title('Cobrança cancelada.')->success()->send();
            });
    }
}
