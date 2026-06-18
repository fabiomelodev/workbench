<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
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
                        Fieldset::make('Contatos')
                            ->schema([
                                TextInput::make('instagram'),
                                TextInput::make('facebook'),
                                TextInput::make('whatsapp'),
                                TextInput::make('email')
                                    ->label('E-mail')
                                    ->email(),
                                TextInput::make('website')
                                    ->label('Site')
                                    ->url()
                                    ->columnSpanFull()
                                    ->copyable(copyMessage: 'Copied!', copyMessageDuration: 1500),
                            ])
                    ]),
                Section::make()
                    ->columnSpan(3)
                    ->schema([
                        DatePicker::make('created_at')
                            ->label('Data de Criação')
                            ->disabled()
                            ->visibleOn('edit'),
                        Select::make('city_id')
                            ->label('Cidade')
                            ->relationship('city', 'name')
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nome')
                                    ->required(),
                            ]),
                        Select::make('niche_id')
                            ->label('Nicho')
                            ->relationship('niche', 'name')
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nome')
                                    ->required(),
                            ]),
                        Select::make('status')
                            ->options(['active' => 'Ativo', 'inactive' => 'Inativo'])
                            ->default('active')
                            ->required(),
                    ])
            ]);
    }
}
