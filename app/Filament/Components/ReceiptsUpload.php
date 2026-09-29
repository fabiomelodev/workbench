<?php

namespace App\Filament\Components;

use App\Models\Payment;
use Filament\Forms\Components\FileUpload;

/** Campo de comprovantes da parcela (imagens ou PDF, no disco privado). */
class ReceiptsUpload
{
    public static function make(): FileUpload
    {
        return FileUpload::make('receipts')
            ->label('Comprovantes')
            ->helperText('Imagens (JPG, PNG, WEBP) ou PDF, até 10 MB cada.')
            ->disk(Payment::RECEIPTS_DISK)
            ->directory('receipts')
            ->visibility('private')
            ->multiple()
            ->maxFiles(10)
            ->maxSize(10240)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
            ->openable()
            ->downloadable()
            ->reorderable()
            ->panelLayout('grid');
    }
}
