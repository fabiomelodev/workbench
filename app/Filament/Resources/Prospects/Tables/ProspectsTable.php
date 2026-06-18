<?php

namespace App\Filament\Resources\Prospects\Tables;

use App\Models\Prospect;
use Filament\Actions\{BulkAction, BulkActionGroup, DeleteAction, DeleteBulkAction, EditAction};
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ProspectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('proposal.name')
                    ->label('Proposta')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('channel')
                    ->label('Canal Usado')
                    ->formatStateUsing(fn(string $state): string => Prospect::getChannel($state)),
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
                TextColumn::make('created_at')
                    ->label('Criado Em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->label('Canal Usado')
                    ->options(Prospect::getTypeChannels()),
                SelectFilter::make('proposal_id')
                    ->label('Proposta')
                    ->relationship('proposal', 'name'),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton(),
                DeleteAction::make()
                    ->iconButton()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('add_next_action')
                        ->label('Adicionar Próxima Ação')
                        ->icon(Heroicon::PlusSmall)
                        ->schema([
                            DatePicker::make('next_action')
                                ->label('Próxima Ação'),
                        ])
                        ->action(function (Collection $records, $data, $livewire, $form) {
                            $records->each(function (Model $record) use ($data) {
                                $record->next_action = $data['next_action'];

                                $record->save();
                            });

                            Notification::make()
                                ->title('Alterado com Sucesso!')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                    BulkAction::make('status_toggle')
                        ->label('Alterar Status')
                        ->icon(Heroicon::CheckCircle)
                        ->schema([
                            Select::make('status')
                                ->options(['active' => 'Ativo', 'inactive' => 'Inativo'])
                                ->default('active')
                                ->required(),
                        ])
                        ->action(function (Collection $records, $data) {
                            $records->each(function (Model $record) use ($data) {
                                $record->status = $data['status'];

                                $record->save();
                            });

                            Notification::make()
                                ->title('Alterado com Sucesso!')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
