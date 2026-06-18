<?php

namespace App\Filament\Resources\Niches\Pages;

use App\Filament\Resources\Niches\NicheResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNiches extends ListRecords
{
    protected static string $resource = NicheResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
