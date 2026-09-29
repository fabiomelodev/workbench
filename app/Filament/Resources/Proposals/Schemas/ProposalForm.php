<?php

namespace App\Filament\Resources\Proposals\Schemas;

use App\Models\Proposal;
use Filament\Forms\Components\{DatePicker, FileUpload, Select, TextInput};
use Filament\Schemas\Components\{Section, View};
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProposalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make()
                    ->columnSpan(9)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required(),
                        TextInput::make('website')
                            ->label('Site')
                            ->url(),
                        FileUpload::make('document')
                            ->label('Proposta enviada (PDF)')
                            ->helperText('PDF de preferência (imagem também é aceita), até 20 MB. Ao salvar, aparece no visualizador abaixo.')
                            ->disk(Proposal::DOCUMENT_DISK)
                            ->directory('proposals')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(20480),
                    ]),
                Section::make()
                    ->columnSpan(3)
                    ->schema([
                        DatePicker::make('created_at')
                            ->label('Data de Criação')
                            ->disabled()
                            ->visibleOn('edit'),
                        TextInput::make('amount')
                            ->label('Orçamento')
                            ->prefix('R$')
                            ->required()
                            ->numeric()
                            ->default(0.0),
                        Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'closed_budget' => 'Orçamento Fechado',
                                'signature' => 'Assinatura'
                            ])
                            ->required(),
                        Select::make('customer_id')
                            ->label('Cliente')
                            ->relationship('customer', 'name')
                            ->required(),
                        Select::make('status')
                            ->options(['active' => 'Ativo', 'inactive' => 'Inativo'])
                            ->default('active')
                            ->required(),
                    ]),
                // Visualizador do PDF salvo (só na edição).
                Section::make('Visualizar proposta')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->collapsible()
                    ->columnSpanFull()
                    ->visibleOn('edit')
                    ->schema([
                        View::make('filament.components.proposal-document-viewer')
                            ->viewData(fn (?Proposal $record): array => ['proposal' => $record]),
                    ]),
            ]);
    }
}
