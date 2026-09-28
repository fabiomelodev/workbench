<?php

namespace App\Filament\Widgets;

use App\Filament\Actions\MarkPaymentPaidAction;
use App\Filament\Resources\Transactions\TransactionResource;
use App\Helpers\FormatCurrency;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Parcelas em aberto (atrasadas primeiro), com "Marcar como paga" em 1 clique. */
class UpcomingPaymentsTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Parcelas em aberto')
            ->query(fn (): Builder => Payment::query()->open()->with('transaction.proposal.customer'))
            ->defaultSort('due_date')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Nenhuma parcela em aberto.')
            ->columns([
                TextColumn::make('transaction.proposal.customer.name')
                    ->label('Cliente'),
                TextColumn::make('number')
                    ->label('Parcela')
                    ->formatStateUsing(fn (Payment $record): string => $record->transaction?->installments
                        ? $record->number . '/' . $record->transaction->installments
                        : $record->number . 'ª mensalidade'),
                TextColumn::make('amount')
                    ->label('Valor')
                    ->formatStateUsing(fn (Payment $record): string => FormatCurrency::getFormatCurrency($record->amount)),
                TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->state(fn (Payment $record): string => $record->statusLabel())
                    ->color(fn (Payment $record): string => $record->statusColor()),
            ])
            ->recordActions([
                MarkPaymentPaidAction::make(),
                Action::make('open')
                    ->label('Ver cobrança')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->iconButton()
                    ->url(fn (Payment $record): string => TransactionResource::getUrl('view', ['record' => $record->transaction_id])),
            ]);
    }
}
