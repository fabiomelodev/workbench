<?php

namespace App\Filament\Actions;

use App\Helpers\FormatCurrency;
use App\Models\Proposal;
use App\Services\ChargeService;
use Filament\Actions\Action;
use Filament\Forms\Components\{DatePicker, Select, Textarea, TextInput};
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\{Get, Set};
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * "Gerar cobrança": cria a Transaction de uma proposta com as parcelas já
 * previstas. Usada na linha da tabela de Propostas (a proposta vem do registro)
 * e no cabeçalho de Cobranças (a proposta é escolhida no formulário).
 */
class GenerateChargeAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'generateCharge';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Gerar cobrança')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->slideOver()
            ->modalHeading('Gerar cobrança')
            ->modalDescription(fn (?Model $record): ?string => $record instanceof Proposal ? $record->customer?->name : null)
            ->modalSubmitActionLabel('Gerar cobrança')
            // Uma cobrança vigente por proposta.
            ->hidden(fn (?Model $record): bool => $record instanceof Proposal && $record->activeTransaction() !== null)
            ->fillForm(fn (?Model $record): array => [
                'amount' => $record instanceof Proposal ? $record->amount : null,
                'installments' => 1,
                'first_due_date' => today()->toDateString(),
            ])
            ->schema([
                Section::make()
                    ->columns(2)
                    ->schema([
                        Select::make('proposal_id')
                            ->label('Proposta')
                            ->options(fn (): array => Proposal::query()
                                ->whereDoesntHave('transactions', fn (Builder $query) => $query->notCanceled())
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (?string $state, Set $set) => $set('amount', Proposal::find($state)?->amount))
                            ->visible(fn (?Model $record): bool => ! $record instanceof Proposal)
                            ->columnSpanFull(),
                        TextEntry::make('charge_type')
                            ->label('Tipo')
                            ->badge()
                            ->state(fn (Get $get, ?Model $record): string => static::resolveProposal($record, $get)?->isSubscription()
                                ? 'Assinatura (mensalidade)'
                                : 'Orçamento fechado')
                            ->columnSpanFull(),
                        TextInput::make('amount')
                            ->label(fn (Get $get, ?Model $record): string => static::resolveProposal($record, $get)?->isSubscription()
                                ? 'Valor da mensalidade'
                                : 'Valor total fechado')
                            ->prefix('R$')
                            ->numeric()
                            ->minValue(0.01)
                            ->required()
                            ->live(onBlur: true),
                        Select::make('installments')
                            ->label('Parcelas')
                            ->options(collect(range(1, 24))->mapWithKeys(fn (int $n) => [$n => $n . 'x'])->all())
                            ->required()
                            ->live()
                            ->hidden(fn (Get $get, ?Model $record): bool => (bool) static::resolveProposal($record, $get)?->isSubscription()),
                        DatePicker::make('first_due_date')
                            ->label(fn (Get $get, ?Model $record): string => static::resolveProposal($record, $get)?->isSubscription()
                                ? '1º vencimento (repete todo mês)'
                                : '1º vencimento')
                            ->required()
                            ->live(),
                        TextEntry::make('preview')
                            ->label('Parcelas previstas')
                            ->state(fn (Get $get, ?Model $record): array => static::preview($get, $record))
                            ->listWithLineBreaks()
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Observação (opcional)')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ])
            ->action(function (?Model $record, array $data, ChargeService $charges) {
                $proposal = $record instanceof Proposal ? $record : Proposal::find($data['proposal_id'] ?? null);

                if (! $proposal) {
                    Notification::make()->title('Proposta não encontrada.')->danger()->send();

                    return;
                }

                $charges->create(
                    $proposal,
                    (float) $data['amount'],
                    $proposal->isSubscription() ? null : (int) $data['installments'],
                    Carbon::parse($data['first_due_date']),
                    $data['notes'] ?? null,
                );

                Notification::make()->title('Cobrança gerada!')->success()->send();
            });
    }

    protected static function resolveProposal(?Model $record, Get $get): ?Proposal
    {
        return $record instanceof Proposal ? $record : Proposal::find($get('proposal_id'));
    }

    /** Prévia das parcelas: "1ª · 10/10/2026 · R$ 222,50". */
    protected static function preview(Get $get, ?Model $record): array
    {
        $amount = (float) $get('amount');
        $firstDue = $get('first_due_date');

        if ($amount <= 0 || blank($firstDue)) {
            return ['Informe o valor e o 1º vencimento.'];
        }

        $firstDue = Carbon::parse($firstDue);

        if (static::resolveProposal($record, $get)?->isSubscription()) {
            return [FormatCurrency::getFormatCurrency($amount) . ' todo dia ' . $firstDue->day
                . ', a partir de ' . $firstDue->format('d/m/Y') . '.'];
        }

        return collect(ChargeService::splitInstallments($amount, (int) ($get('installments') ?: 1)))
            ->map(fn (float $value, int $index) => ($index + 1) . 'ª · '
                . $firstDue->copy()->addMonthsNoOverflow($index)->format('d/m/Y') . ' · '
                . FormatCurrency::getFormatCurrency($value))
            ->all();
    }
}
