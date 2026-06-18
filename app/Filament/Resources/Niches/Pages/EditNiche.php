<?php

namespace App\Filament\Resources\Niches\Pages;

use App\Filament\Resources\Niches\NicheResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNiche extends EditRecord
{
    protected static string $resource = NicheResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
