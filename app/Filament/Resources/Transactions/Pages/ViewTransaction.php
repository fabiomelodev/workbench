<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Resources\Transactions\Tables\TransactionsTable;
use App\Filament\Resources\Transactions\TransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Cobrança · ' . ($this->getRecord()->proposal?->customer?->name ?? '#' . $this->getRecord()->id);
    }

    protected function getHeaderActions(): array
    {
        return [
            TransactionsTable::cancelAction(),
            DeleteAction::make(),
        ];
    }
}
