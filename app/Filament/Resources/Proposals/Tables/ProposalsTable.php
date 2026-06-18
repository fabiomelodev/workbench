<?php

namespace App\Filament\Resources\Proposals\Tables;

use App\Helpers\FormatCurrency;
use Filament\Actions\{BulkAction, BulkActionGroup, DeleteAction, DeleteBulkAction, EditAction};
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\{IconColumn, TextColumn};
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\{Collection, Model};

class ProposalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name', 'asc')
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'closed_budget' => 'Orçamento Fechado',
                        'signature' => 'Assinatura'
                    }),
                TextColumn::make('amount')
                    ->label('Orçamento')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn(string $state): string => FormatCurrency::getFormatCurrency($state)),
                IconColumn::make('website')
                    ->label('Site')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->url(fn(string $state): string => $state, true),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'active' => 'Ativo',
                        'inactive' => 'Inativo',
                    })
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
                TextColumn::make('created_at')
                    ->label('Criado Em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Ativo',
                        'inactive' => 'Inativo',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton(),
                DeleteAction::make()
                    ->iconButton()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
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
