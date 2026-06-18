<?php

namespace App\Filament\Resources\Proposals\Schemas;

use Filament\Forms\Components\{DatePicker, Select, TextInput};
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                    ])




            ]);
    }
}
