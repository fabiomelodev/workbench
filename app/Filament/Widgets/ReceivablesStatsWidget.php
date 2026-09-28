<?php

namespace App\Filament\Widgets;

use App\Helpers\FormatCurrency;
use App\Models\Payment;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

/**
 * Recebimentos (caixa), a partir das parcelas das cobranças: o que entrou no
 * mês, o que vence no mês, o total a receber e o que está atrasado.
 */
class ReceivablesStatsWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'Recebimentos';

    protected function getStats(): array
    {
        $month = [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];
        $monthName = now()->locale('pt_BR')->translatedFormat('F');

        return [
            $this->stat('Recebido em ' . $monthName, Payment::query()->paid()->whereBetween('paid_at', $month), 'Parcelas pagas no mês')
                ->descriptionIcon(Heroicon::OutlinedArrowDownTray)
                ->color('success'),
            $this->stat('A receber em ' . $monthName, Payment::query()->open()->whereBetween('due_date', $month), 'Vencem no mês e estão em aberto')
                ->descriptionIcon(Heroicon::OutlinedCalendar)
                ->color('primary'),
            $this->stat('Total a receber', Payment::query()->open(), 'Todas as parcelas em aberto')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('warning'),
            $this->stat('Atrasado', Payment::query()->open()->where('due_date', '<', today()->toDateString()), 'Vencidas e não pagas')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger'),
        ];
    }

    protected function stat(string $label, Builder $query, string $description): Stat
    {
        $count = (clone $query)->count();

        return Stat::make($label, FormatCurrency::getFormatCurrency($query->sum('amount')))
            ->description($description . ' · ' . $count . ' ' . ($count === 1 ? 'parcela' : 'parcelas'));
    }
}
