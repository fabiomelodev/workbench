<?php

namespace App\Livewire;

use App\Filament\Actions\{AttemptsAction, ContactCenterAction, ProposalAction};
use App\Helpers\FormatCurrency;
use App\Models\Prospect;
use Filament\Actions\{Action, ActionGroup, BulkAction, BulkActionGroup, DeleteAction, EditAction};
use Filament\Forms\Components\{DatePicker, Select};
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\{IconColumn, SelectColumn, TextColumn};
use Filament\Tables\Filters\{Filter, SelectFilter};
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\{Builder, Collection, Model};
use Illuminate\Support\Str;

/**
 * Tabela única de trabalho do dia, com abas Atrasados / Hoje / Amanhã.
 * Substitui "Prospectar Hoje" e "Prospectar Amanhã" e ainda revela os
 * atrasados (que antes sumiam silenciosamente da lista de "hoje").
 */
class ProspectsToWorkTable extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'livewire.prospects-to-work-table';

    public string $activeTab = 'today';

    /** Status considerados "encerrados" — não viram atraso para cobrar. */
    protected function finishedStatuses(): array
    {
        return [Prospect::HIRED, Prospect::CLOSED, Prospect::NO_RESPONSE];
    }

    public function tabs(): array
    {
        return [
            'overdue' => 'Atrasados',
            'today' => 'Hoje',
            'tomorrow' => 'Amanhã',
        ];
    }

    protected function queryFor(string $tab): Builder
    {
        return match ($tab) {
            'overdue' => Prospect::query()
                ->whereDate('next_action', '<', now())
                ->whereNotIn('status', $this->finishedStatuses()),
            'tomorrow' => Prospect::query()
                ->whereDate('next_action', now()->addDay()),
            default => Prospect::query()
                ->where(fn(Builder $q) => $q->whereDate('next_action', now())->orWhereDate('last_action', now())),
        };
    }

    public function countFor(string $tab): int
    {
        return $this->queryFor($tab)->count();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Para Prospectar')
            ->paginated([10, 25, 50, 'all'])
            ->defaultPaginationPageOption(10)
            ->query(fn(): Builder => $this->queryFor($this->activeTab)->withCount('attempts'))
            ->emptyStateHeading(match ($this->activeTab) {
                'overdue' => 'Nenhum lead atrasado. 👏',
                'tomorrow' => 'Nada agendado para amanhã.',
                default => 'Nada para prospectar hoje.',
            })
            ->columns([
                TextColumn::make('proposal.customer.name')
                    ->label('Empresa')
                    ->formatStateUsing(fn(?string $state): string => Str::limit((string) $state, 28)),
                TextColumn::make('phone_status')
                    ->label('Telefone')
                    ->badge()
                    ->state(fn(Prospect $record): string => $record->proposal?->customer?->phoneTypeLabel() ?? 'Sem número')
                    ->color(fn(Prospect $record): string => $record->proposal?->customer?->phoneTypeColor() ?? 'gray')
                    ->icon(Heroicon::OutlinedPhone)
                    ->url(fn(Prospect $record): ?string => $record->proposal?->customer?->whatsappUrl(), true),
                TextColumn::make('proposal.type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'closed_budget' => 'Orçamento Fechado',
                        'signature' => 'Assinatura'
                    }),
                TextColumn::make('proposal.amount')
                    ->label('Orçamento')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn(?string $state): string => FormatCurrency::getFormatCurrency((string) $state)),
                IconColumn::make('proposal.website')
                    ->label('Site')
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->url(fn(string $state): string => $state, true),
                SelectColumn::make('status')
                    ->options(Prospect::getTypeStatus()),
                TextColumn::make('attempts_count')
                    ->label('Tentativas')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn(?int $state): string => ($state ?? 0) . '/' . Prospect::MAX_ATTEMPTS)
                    ->color(fn(Prospect $record): string => match (true) {
                        ($record->attempts_count ?? 0) >= Prospect::MAX_ATTEMPTS => 'danger',
                        ($record->attempts_count ?? 0) === Prospect::MAX_ATTEMPTS - 1 => 'warning',
                        ($record->attempts_count ?? 0) === 0 => 'gray',
                        default => 'success',
                    })
                    ->tooltip(fn(Prospect $record): ?string => ($record->attempts_count ?? 0) >= Prospect::MAX_ATTEMPTS
                        ? 'Limite de ' . Prospect::MAX_ATTEMPTS . ' tentativas atingido — hora de decidir'
                        : null),
                TextColumn::make('next_action')
                    ->label('Próxima Ação')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('next_action')
                    ->schema([
                        DatePicker::make('date')
                            ->label('Próxima Ação')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['date'], fn(Builder $query, $date): Builder => $query->whereDate('next_action', $date));
                    }),
                Filter::make('proposal_type')
                    ->schema([
                        Select::make('type')
                            ->label('Tipo de proposta')
                            ->options([
                                'closed_budget' => 'Orçamento Fechado',
                                'signature' => 'Assinatura'
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['type'], function (Builder $query, $type): Builder {
                            return $query->with('proposal')->whereHas('proposal', function (Builder $query) use ($type): Builder {
                                return $query->where('type', $type);
                            });
                        });
                    }),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Prospect::getTypeStatus()),
                SelectFilter::make('proposal_id')
                    ->label('Proposta')
                    ->relationship('proposal', 'name'),
            ])
            ->recordActions([
                ContactCenterAction::make(),
                ProposalAction::make(),
                AttemptsAction::make(),
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
                ]),
            ]);
    }

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
