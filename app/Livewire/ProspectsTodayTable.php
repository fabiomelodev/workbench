<?php

namespace App\Livewire;

use App\Helpers\FormatCurrency;
use App\Models\Prospect;
use Filament\Actions\{Action, BulkActionGroup, DeleteAction, EditAction};
use Filament\Forms\Components\{DatePicker, Select, TextInput};
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Str;

class ProspectsTodayTable extends TableWidget
{
    public function table(Table $table): Table
    {
        $query = Prospect::query()->whereDate('next_action', now())->orWhereDate('last_action', now());

        return $table
            ->heading('Prospectar Hoje')
            ->paginated(false)
            ->query(fn() => $query)
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Action::make('customer')
                    ->hiddenLabel()
                    ->icon(Heroicon::User)
                    ->mountUsing(function (Schema $form, Prospect $record) {
                        $customer = $record->proposal?->customer;

                        if ($customer) {
                            $form->fill($customer->toArray());
                        }
                    })
                    ->schema([
                        Section::make()
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Cliente')
                                    ->columnSpanFull(),
                                TextInput::make('instagram'),
                                TextInput::make('facebook'),
                                TextInput::make('whatsapp'),
                                TextInput::make('email'),
                                TextInput::make('website')
                                    ->label('Site')
                                    ->columnSpanFull(),
                            ])
                    ])
                    ->action(function (Prospect $record, array $data) {
                        $customer = $record->proposal?->customer;

                        if ($customer) {
                            $customer->update($data);

                            Notification::make()
                                ->title('Cliente atualizado com sucesso!')
                                ->success()
                                ->send();
                        }
                    }),
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
                DeleteAction::make()
                    ->iconButton()
            ])
            ->toolbarActions([
                BulkActionGroup::make([

                ]),
            ]);
    }
}
