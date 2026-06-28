<?php

namespace App\Livewire;

use App\Filament\Actions\AttemptsAction;
use App\Filament\Actions\ContactCenterAction;
use App\Filament\Actions\ProposalAction;
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
                    //
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
