<?php

namespace App\Filament\Resources\Transactions\RelationManagers;

use App\Filament\Actions\MarkPaymentPaidAction;
use App\Helpers\FormatCurrency;
use App\Models\Payment;
use Filament\Actions\{Action, EditAction};
use Filament\Forms\Components\{DatePicker, Textarea, TextInput};
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Parcelas';

    // Na página de visualização as ações também ficam disponíveis.
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('amount')
                ->label('Valor')
                ->prefix('R$')
                ->numeric()
                ->required(),
            DatePicker::make('due_date')
                ->label('Vencimento')
                ->required(),
            Textarea::make('notes')
                ->label('Observação')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        $installments = $this->getOwnerRecord()->installments;

        return $table
            ->defaultSort('number')
            ->paginated(false)
            ->columns([
                TextColumn::make('number')
                    ->label('Parcela')
                    ->formatStateUsing(fn (int $state): string => $installments ? $state . '/' . $installments : $state . 'ª'),
                TextColumn::make('amount')
                    ->label('Valor')
                    ->formatStateUsing(fn (Payment $record): string => FormatCurrency::getFormatCurrency($record->amount)),
                TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y'),
                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->state(fn (Payment $record): string => $record->statusLabel())
                    ->color(fn (Payment $record): string => $record->statusColor()),
                TextColumn::make('paid_at')
                    ->label('Pago em')
                    ->date('d/m/Y')
                    ->placeholder('—'),
                TextColumn::make('method')
                    ->label('Forma')
                    ->formatStateUsing(fn (Payment $record): ?string => $record->methodLabel())
                    ->placeholder('—'),
                TextColumn::make('notes')
                    ->label('Observação')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                MarkPaymentPaidAction::make(),
                Action::make('undo')
                    ->label('Desfazer')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Desfazer pagamento?')
                    ->visible(fn (Payment $record): bool => $record->isPaid())
                    ->action(fn (Payment $record) => $record->update(['paid_at' => null, 'method' => null])),
                EditAction::make()
                    ->iconButton()
                    ->visible(fn (Payment $record): bool => ! $record->isPaid()),
            ]);
    }
}
