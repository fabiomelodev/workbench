<?php

namespace App\Livewire;

use App\Helpers\FormatCurrency;
use App\Models\Prospect;
use Filament\Actions\{BulkActionGroup, EditAction};
use Filament\Forms\Components\{DatePicker, Select};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProspectsProgressTableWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Em Contato | Aguardando Retorno | Passado P/ Outro Departamento')
            ->paginated(false)
            ->query(fn(): Builder => Prospect::query()->whereIn('status', Prospect::getTypeStatusProgress()))
            ->columns([
                TextColumn::make('proposal.customer.name')
                    ->label('Proposta')
                    ->formatStateUsing(fn(string $state): string => Str::limit($state, 28)),
                TextColumn::make('proposal.amount')
                    ->label('Orçamento')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn(string $state): string => FormatCurrency::getFormatCurrency($state)),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => Prospect::getStatus($state)),
                TextColumn::make('last_action')
                    ->label('Última Ação')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('next_action')
                    ->label('Próxima Ação')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->schema([
                        Select::make('channel')
                            ->label('Canal Usado')
                            ->options(Prospect::getTypeChannels()),
                        DatePicker::make('last_action')
                            ->label('Última Ação'),
                        DatePicker::make('next_action')
                            ->label('Próxima Ação'),
                        Select::make('status')
                            ->options(Prospect::getTypeStatus())
                            ->default('on_hold'),
                    ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
