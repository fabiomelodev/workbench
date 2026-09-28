<?php

namespace App\Filament\Resources\Transactions\Schemas;

use App\Helpers\FormatCurrency;
use App\Models\Transaction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columns(4)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('proposal.customer.name')
                        ->label('Cliente'),
                    TextEntry::make('proposal.name')
                        ->label('Proposta'),
                    TextEntry::make('type')
                        ->label('Tipo')
                        ->badge()
                        ->state(fn (Transaction $record): string => $record->isSubscription() ? 'Assinatura' : 'Orçamento Fechado ' . $record->installments . 'x'),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (Transaction $record): string => $record->statusLabel())
                        ->color(fn (Transaction $record): string => $record->statusColor()),
                    TextEntry::make('amount')
                        ->label(fn (Transaction $record): string => $record->isSubscription() ? 'Mensalidade' : 'Valor total')
                        ->formatStateUsing(fn (Transaction $record): string => FormatCurrency::getFormatCurrency($record->amount)),
                    TextEntry::make('paid')
                        ->label('Recebido')
                        ->state(fn (Transaction $record): string => FormatCurrency::getFormatCurrency($record->paidAmount()))
                        ->color('success'),
                    TextEntry::make('open')
                        ->label('Em aberto')
                        ->state(fn (Transaction $record): string => $record->isCanceled()
                            ? '—'
                            : FormatCurrency::getFormatCurrency($record->openAmount())),
                    TextEntry::make('paid_at')
                        ->label('Quitada em')
                        ->date('d/m/Y')
                        ->placeholder('—'),
                    TextEntry::make('notes')
                        ->label('Observação')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
