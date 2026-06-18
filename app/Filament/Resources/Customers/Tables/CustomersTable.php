<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\{BulkAction, BulkActionGroup, DeleteAction, DeleteBulkAction, EditAction};
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\{IconColumn, TextColumn};
use Filament\Tables\Filters\{Filter, SelectFilter};
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\{Builder, Collection, Model};

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name', 'asc')
            ->paginated([10, 25, 50, 100, 'all'])
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('niche.name')
                    ->label('Nicho')
                    ->badge()
                    ->searchable(),
                IconColumn::make('instagram')
                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                    ->url(fn(string $state): string => $state, true),
                IconColumn::make('facebook')
                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                    ->url(fn(string $state): string => $state, true)
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('whatsapp')
                    ->icon(Heroicon::OutlinedPhone)
                    ->url(fn(string $state): string => $state, true),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('website')
                    ->label('Site')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->url(fn(string $state): string => $state, true),
                TextColumn::make('city.name')
                    ->label('Cidade')
                    ->badge()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('niche_id')
                    ->label('Nicho')
                    ->relationship('niche', 'name'),
                SelectFilter::make('city_id')
                    ->label('Cidade')
                    ->relationship('city', 'name'),
                SelectFilter::make('status')
                    ->default('active')
                    ->options([
                        'active' => 'Ativo',
                        'inactive' => 'Inativo',
                    ]),
                Filter::make('whatsapp')
                    ->schema([
                        Select::make('has_whatsapp')
                            ->label('Tem Whatsapp?')
                            ->default('all')
                            ->options([
                                'all' => 'Todos',
                                1 => 'Sim',
                                0 => 'Não'
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['has_whatsapp'] == 1, fn(Builder $query): Builder => $query->whereNotNull('whatsapp'))
                            ->when($data['has_whatsapp'] == 0, fn(Builder $query): Builder => $query->whereNull('whatsapp'));
                    }),
                Filter::make('website')
                    ->schema([
                        Select::make('has_website')
                            ->label('Tem Site?')
                            ->default('all')
                            ->options([
                                'all' => 'Todos',
                                1 => 'Sim',
                                0 => 'Não'
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['has_website'] == 1, fn(Builder $query): Builder => $query->whereNotNull('website'))
                            ->when($data['has_website'] == 0, fn(Builder $query): Builder => $query->whereNull('website'));
                    })
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
