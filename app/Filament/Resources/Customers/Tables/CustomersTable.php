<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Filament\Actions\ContactCenterAction;
use App\Models\Customer;
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
                TextColumn::make('phone_type')
                    ->label('Telefone')
                    ->badge()
                    ->icon(Heroicon::OutlinedPhone)
                    ->formatStateUsing(fn(?string $state, Customer $record): string => $record->phoneTypeLabel())
                    ->color(fn(Customer $record): string => $record->phoneTypeColor())
                    ->url(fn(Customer $record): ?string => $record->whatsappUrl(), true)
                    ->sortable(),
                TextColumn::make('data_score')
                    ->label('Dados')
                    ->badge()
                    ->state(fn(Customer $record): string => $record->dataScore() . '/' . $record->dataTotal())
                    ->color(fn(Customer $record): string => match (true) {
                        $record->dataScore() >= 3 => 'success',
                        $record->dataScore() === 0 => 'danger',
                        default => 'warning',
                    })
                    ->tooltip(fn(Customer $record): string => $record->missingData()
                        ? 'Falta: ' . implode(', ', $record->missingData())
                        : 'Cadastro completo'),
                IconColumn::make('instagram')
                    ->label('Insta')
                    ->icon(Heroicon::OutlinedAtSymbol)
                    ->color(fn(Customer $record): string => $record->instagramUrl() ? 'primary' : 'gray')
                    ->url(fn(Customer $record): ?string => $record->instagramUrl(), true),
                IconColumn::make('website')
                    ->label('Site')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->color(fn(Customer $record): string => $record->websiteUrl() ? 'primary' : 'gray')
                    ->url(fn(Customer $record): ?string => $record->websiteUrl(), true),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                        default => $state,
                    })
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('niche_id')
                    ->label('Nicho(s)')
                    ->relationship('niche', 'name')
                    ->multiple(),
                SelectFilter::make('city_id')
                    ->label('Cidade')
                    ->relationship('city', 'name'),
                SelectFilter::make('phone_type')
                    ->label('Tipo de telefone')
                    ->options([
                        'mobile' => '📱 Celular',
                        'landline' => '☎️ Fixo',
                        'invalid' => '⚠️ Inválido',
                    ]),
                SelectFilter::make('status')
                    ->default('active')
                    ->options([
                        'active' => 'Ativo',
                        'inactive' => 'Inativo',
                    ]),
                Filter::make('whatsapp')
                    ->schema([
                        Select::make('has_whatsapp')
                            ->label('Tem WhatsApp válido?')
                            ->default('all')
                            ->options([
                                'all' => 'Todos',
                                1 => 'Sim',
                                0 => 'Não',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['has_whatsapp'] == 1, fn(Builder $query): Builder => $query->where('phone_type', 'mobile'))
                            ->when($data['has_whatsapp'] === '0', fn(Builder $query): Builder => $query->where(fn(Builder $q) => $q->where('phone_type', '!=', 'mobile')->orWhereNull('phone_type')));
                    }),
                Filter::make('instagram')
                    ->schema([
                        Select::make('has_instagram')
                            ->label('Tem Instagram?')
                            ->default('all')
                            ->options([
                                'all' => 'Todos',
                                1 => 'Sim',
                                0 => 'Não',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['has_instagram'] == 1, fn(Builder $query): Builder => $query->whereNotNull('instagram')->where('instagram', '!=', ''))
                            ->when($data['has_instagram'] === '0', fn(Builder $query): Builder => $query->where(fn(Builder $q) => $q->whereNull('instagram')->orWhere('instagram', '=', '')));
                    }),
                Filter::make('website')
                    ->schema([
                        Select::make('has_website')
                            ->label('Tem Site?')
                            ->default('all')
                            ->options([
                                'all' => 'Todos',
                                1 => 'Sim',
                                0 => 'Não',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['has_website'] == 1, fn(Builder $query): Builder => $query->whereNotNull('website')->where('website', '!=', ''))
                            ->when($data['has_website'] === '0', fn(Builder $query): Builder => $query->where(fn(Builder $q) => $q->whereNull('website')->orWhere('website', '=', '')));
                    }),
            ])
            ->recordActions([
                ContactCenterAction::make(),
                EditAction::make()
                    ->iconButton(),
                DeleteAction::make()
                    ->iconButton(),
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
