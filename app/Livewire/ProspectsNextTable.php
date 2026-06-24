<?php

namespace App\Livewire;

use App\Filament\Actions\ContactCenterAction;
use App\Models\Prospect;
use Filament\Actions\{BulkActionGroup, DeleteAction, EditAction};
use Filament\Forms\Components\{DatePicker, Select};
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
                TextColumn::make('proposal.customer.name')
                    ->label('Empresa'),
                TextColumn::make('phone_status')
                    ->label('Telefone')
                    ->badge()
                    ->state(fn(Prospect $record): string => $record->proposal?->customer?->phoneTypeLabel() ?? 'Sem número')
                    ->color(fn(Prospect $record): string => $record->proposal?->customer?->phoneTypeColor() ?? 'gray')
                    ->icon(Heroicon::OutlinedPhone)
                    ->url(fn(Prospect $record): ?string => $record->proposal?->customer?->whatsappUrl(), true),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => Prospect::getStatus($state)),
            ])
            ->recordActions([
                ContactCenterAction::make(),
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
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
