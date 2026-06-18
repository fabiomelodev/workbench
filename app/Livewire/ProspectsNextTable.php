<?php

namespace App\Livewire;

use App\Models\Prospect;
use Filament\Actions\{Action, BulkActionGroup, DeleteAction, EditAction};
use Filament\Forms\Components\{DatePicker, Select, TextInput};
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ProspectsNextTable extends TableWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->heading('Prospectar Amanhã')
            ->paginated(false)
            ->query(fn() => Prospect::query()->whereDate('next_action', now()->addDay()))
            ->columns([
                TextColumn::make('proposal.name')
                    ->label('Proposta'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => Prospect::getStatus($state)),
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
                    // No Filament v5, você pode omitir a tipagem do formulário ou usar a própria Action para dar o fill
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

                            \Filament\Notifications\Notification::make()
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
                    //
                ]),
            ]);
    }
}
