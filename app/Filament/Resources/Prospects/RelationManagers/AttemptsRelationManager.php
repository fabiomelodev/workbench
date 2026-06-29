<?php

namespace App\Filament\Resources\Prospects\RelationManagers;

use App\Models\Prospect;
use App\Models\ProspectAttempt;
use Filament\Actions\{CreateAction, DeleteAction, EditAction};
use Filament\Forms\Components\{DatePicker, Select, Textarea};
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'attempts';

    protected static ?string $title = 'Histórico de tentativas';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('channel')
                ->label('Meio de canal')
                ->options(Prospect::getTypeChannels())
                ->required(),
            Select::make('outcome')
                ->label('Desfecho')
                ->options(ProspectAttempt::getOutcomes())
                ->default(ProspectAttempt::OUTCOME_NO_ANSWER)
                ->required(),
            DatePicker::make('attempted_at')
                ->label('Data')
                ->default(now())
                ->required(),
            Textarea::make('notes')
                ->label('Observação')
                ->rows(2)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('channel')
            ->defaultSort('attempted_at', 'asc')
            ->columns([
                TextColumn::make('attempted_at')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('channel')
                    ->label('Meio de canal')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => $state ? Prospect::getChannel($state) : '—'),
                TextColumn::make('outcome')
                    ->label('Desfecho')
                    ->badge()
                    ->formatStateUsing(fn(ProspectAttempt $record): string => $record->outcomeLabel())
                    ->color(fn(ProspectAttempt $record): string => $record->outcomeColor()),
                TextColumn::make('notes')
                    ->label('Observação')
                    ->limit(60)
                    ->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar tentativa'),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton(),
                DeleteAction::make()
                    ->iconButton(),
            ]);
    }
}
