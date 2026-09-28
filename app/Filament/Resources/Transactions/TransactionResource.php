<?php

namespace App\Filament\Resources\Transactions;

use App\Filament\Resources\Transactions\Pages\{ListTransactions, ViewTransaction};
use App\Filament\Resources\Transactions\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\Transactions\Schemas\TransactionInfolist;
use App\Filament\Resources\Transactions\Tables\TransactionsTable;
use App\Models\Transaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $label = 'Cobrança';

    protected static ?string $pluralLabel = 'Cobranças';

    // Logo abaixo de "Financeiro" (-1) no menu.
    protected static ?int $navigationSort = 0;

    public static function getNavigationBadge(): ?string
    {
        $count = Transaction::query()->where('status', Transaction::PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return TransactionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactions::route('/'),
            'view' => ViewTransaction::route('/{record}'),
        ];
    }
}
