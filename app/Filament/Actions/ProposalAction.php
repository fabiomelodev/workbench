<?php

namespace App\Filament\Actions;

use App\Models\Customer;
use App\Models\Prospect;
use App\Models\Proposal;
use Filament\Actions\Action;
use Filament\Forms\Components\{Select, TextInput, Toggle};
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * Mostra a proposta vinculada a uma prospecção num slide-over (no mesmo estilo
 * da Central de Contato) e permite editá-la: nome, orçamento, tipo, site e status.
 */
class ProposalAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'proposal';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Proposta')
            ->icon(Heroicon::OutlinedDocumentText)
            ->color('gray')
            ->slideOver()
            ->modalHeading('Proposta')
            ->modalDescription(fn (Model $record): ?string => static::resolveProposal($record)?->customer?->name)
            ->modalSubmitActionLabel('Salvar proposta')
            ->fillForm(function (Model $record): array {
                $proposal = static::resolveProposal($record);

                if (! $proposal) {
                    return [];
                }

                return [
                    ...$proposal->only(['name', 'amount', 'type', 'website', 'status']),
                    'is_hired' => $proposal->isHired(),
                ];
            })
            ->schema([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('amount')
                            ->label('Orçamento')
                            ->prefix('R$')
                            ->numeric()
                            ->required(),
                        Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'closed_budget' => 'Orçamento Fechado',
                                'signature' => 'Assinatura',
                            ])
                            ->required(),
                        TextInput::make('website')
                            ->label('Site')
                            ->url()
                            ->columnSpanFull(),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Ativo',
                                'inactive' => 'Inativo',
                            ])
                            ->required(),
                        // Calculado: não é coluna da tabela, então não vai para o update().
                        Toggle::make('is_hired')
                            ->label('Foi contratado')
                            ->helperText('Marcado automaticamente quando alguma prospecção desta proposta está como "Contratado".')
                            ->onColor('success')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
            ])
            ->action(function (Model $record, array $data) {
                $proposal = static::resolveProposal($record);

                if (! $proposal) {
                    Notification::make()->title('Proposta não encontrada.')->danger()->send();

                    return;
                }

                $proposal->update($data);

                Notification::make()->title('Proposta atualizada com sucesso!')->success()->send();
            });
    }

    public static function resolveProposal(Model $record): ?Proposal
    {
        if ($record instanceof Proposal) {
            return $record;
        }

        if ($record instanceof Prospect) {
            return $record->proposal;
        }

        if ($record instanceof Customer) {
            return $record->proposals()->first();
        }

        return null;
    }
}
