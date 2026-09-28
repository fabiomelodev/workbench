<?php

namespace App\Filament\Resources\Transactions\Pages;

use App\Filament\Actions\GenerateChargeAction;
use App\Filament\Resources\Transactions\TransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListTransactions extends ListRecords
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GenerateChargeAction::make(),
        ];
    }
}
