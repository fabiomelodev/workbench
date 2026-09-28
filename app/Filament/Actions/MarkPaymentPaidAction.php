<?php

namespace App\Filament\Actions;

use App\Models\{Payment, Transaction};
use Filament\Actions\Action;
use Filament\Forms\Components\{DatePicker, Select, Textarea};
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/** Marca uma parcela como paga (data + forma de pagamento). */
class MarkPaymentPaidAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'markPaid';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Marcar como paga')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->modalHeading(fn (Payment $record): string => 'Registrar pagamento da ' . $record->number . 'ª parcela')
            ->modalWidth('md')
            ->visible(fn (Payment $record): bool => ! $record->isPaid() && ! $record->transaction?->isCanceled())
            ->fillForm(fn (Payment $record): array => [
                'paid_at' => today()->toDateString(),
                'method' => $record->method ?? 'pix',
                'notes' => $record->notes,
            ])
            ->schema([
                DatePicker::make('paid_at')
                    ->label('Pago em')
                    ->required(),
                Select::make('method')
                    ->label('Forma de pagamento')
                    ->options(Payment::getMethods())
                    ->required(),
                Textarea::make('notes')
                    ->label('Observação (opcional)')
                    ->rows(2),
            ])
            ->action(function (Payment $record, array $data) {
                $record->update($data);

                Notification::make()
                    ->title('Pagamento registrado!')
                    ->body($record->transaction?->refresh()->status === Transaction::PAID ? 'Todas as parcelas pagas: cobrança quitada. 🎉' : null)
                    ->success()
                    ->send();
            });
    }
}
