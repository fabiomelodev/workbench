<?php

namespace App\Livewire;

use App\Filament\Actions\ContactCenterAction;
use App\Helpers\FormatCurrency;
use App\Models\Prospect;
use Filament\Actions\{Action, ActionGroup, BulkActionGroup, DeleteAction, EditAction};
use Filament\Forms\Components\{DatePicker, Select};
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\{SelectColumn, TextColumn};
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProspectsTodayTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $query = Prospect::query()->where(function (Builder $query) {
            $query->whereDate('next_action', now())
                ->orWhereDate('last_action', now());
        });

        return $table
            ->heading('Prospectar Hoje')
            ->paginated(false)
            ->query(fn() => $query)
            ->columns([
                TextColumn::make('proposal.customer.name')
                    ->label('Empresa')
                    ->formatStateUsing(fn(string $state): string => Str::limit($state, 28)),
                TextColumn::make('phone_status')
                    ->label('Telefone')
                    ->badge()
                    ->state(fn(Prospect $record): string => $record->proposal?->customer?->phoneTypeLabel() ?? 'Sem número')
                    ->color(fn(Prospect $record): string => $record->proposal?->customer?->phoneTypeColor() ?? 'gray')
                    ->icon(Heroicon::OutlinedPhone)
                    ->url(fn(Prospect $record): ?string => $record->proposal?->customer?->whatsappUrl(), true),
                TextColumn::make('proposal.amount')
                    ->label('Orçamento')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn(string $state): string => FormatCurrency::getFormatCurrency($state)),
                SelectColumn::make('status')
                    ->options(Prospect::getTypeStatus()),
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
            ->recordActions([
                ContactCenterAction::make(),
                ActionGroup::make([
                    $this->snoozeAction('snooze_1', '+1 dia', 1),
                    $this->snoozeAction('snooze_3', '+3 dias', 3),
                    $this->snoozeAction('snooze_7', '+7 dias', 7),
                ])
                    ->label('Reagendar')
                    ->icon(Heroicon::Calendar)
                    ->button(),
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

    /** Botão de reagendamento rápido da próxima ação. */
    protected function snoozeAction(string $name, string $label, int $days): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon(Heroicon::ArrowRight)
            ->action(function (Prospect $record) use ($days, $label) {
                $record->update([
                    'last_action' => now(),
                    'next_action' => now()->addDays($days),
                ]);

                Notification::make()
                    ->title("Reagendado para {$label}.")
                    ->success()
                    ->send();
            });
    }
}
