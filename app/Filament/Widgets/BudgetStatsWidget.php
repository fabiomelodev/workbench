<?php

namespace App\Filament\Widgets;

use App\Helpers\FormatCurrency;
use App\Models\{Proposal, Prospect};
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * Orçamentos das propostas: em aberto (ativas e ainda não contratadas),
 * contratado no total e contratado no mês atual (pela data em que a
 * prospecção virou "Contratado").
 */
class BudgetStatsWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Orçamentos';

    protected function getStats(): array
    {
        $hired = fn(Builder $query): Builder => $query->where('status', Prospect::HIRED);

        $open = Proposal::query()->active()->whereDoesntHave('prospects', $hired);
        $hiredTotal = Proposal::query()->whereHas('prospects', $hired);
        $hiredThisMonth = Proposal::query()->whereHas('prospects', fn(Builder $query): Builder => $hired($query)
            ->whereBetween('hired_at', [now()->startOfMonth(), now()->endOfMonth()]));

        return [
            $this->budgetStat('Orçamento não contratado', $open, 'Propostas ativas em aberto')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('warning'),
            $this->budgetStat('Orçamento contratado', $hiredTotal, 'Total contratado')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success'),
            $this->budgetStat('Contratado em ' . now()->locale('pt_BR')->translatedFormat('F'), $hiredThisMonth, 'Contratado no mês atual')
                ->descriptionIcon(Heroicon::OutlinedCalendar)
                ->color('primary'),
        ];
    }

    protected function budgetStat(string $label, Builder $query, string $description): Stat
    {
        $count = (clone $query)->count();

        return Stat::make($label, FormatCurrency::getFormatCurrency($query->sum('amount')))
            ->description($description . ' · ' . $count . ' ' . ($count === 1 ? 'proposta' : 'propostas'));
    }
}
