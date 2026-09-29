<?php

namespace App\Filament\Actions;

use App\Filament\Components\ReceiptsUpload;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Comprovantes da parcela: ver, anexar ou remover a qualquer momento,
 * inclusive depois de a parcela já estar paga.
 */
class PaymentReceiptsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'receipts';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(fn (Payment $record): string => 'Comprovantes' . ($record->receiptsCount() ? ' (' . $record->receiptsCount() . ')' : ''))
            ->icon(Heroicon::OutlinedPaperClip)
            ->color(fn (Payment $record): string => $record->receiptsCount() ? 'primary' : 'gray')
            ->modalHeading(fn (Payment $record): string => 'Comprovantes da ' . $record->number . 'ª parcela')
            ->modalWidth('lg')
            ->modalSubmitActionLabel('Salvar comprovantes')
            ->fillForm(fn (Payment $record): array => ['receipts' => $record->receipts ?? []])
            ->schema([
                ReceiptsUpload::make(),
            ])
            ->action(function (Payment $record, array $data) {
                $record->update(['receipts' => $data['receipts'] ?? []]);

                Notification::make()->title('Comprovantes salvos!')->success()->send();
            });
    }
}
