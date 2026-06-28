<?php

namespace App\Filament\Actions;

use App\Models\Customer;
use App\Models\Prospect;
use App\Models\Proposal;
use Filament\Actions\Action;
use Filament\Forms\Components\{Select, TextInput};
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
            ->fillForm(fn (Model $record): array => static::resolveProposal($record)?->only([
                'name', 'amount', 'type', 'website', 'status',
            ]) ?? [])
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
