<?php

namespace App\Filament\Widgets;

use App\Models\{Customer, Proposal, Prospect};
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ControlStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $customersTotal = Customer::query()->active()->get()->count();

        $proposalsTotal = Proposal::query()->active()->get()->count();

        $prospectsTotal = Prospect::query()->whereIn('status', Prospect::getTypeStatusProgress())->get()->count();

        return [
            Stat::make('Cliente(s) Ativo(s)', $customersTotal),
            Stat::make('Proposta(s) Ativa(s)', $proposalsTotal),
            Stat::make('Prospecções em Andamento(s)', $prospectsTotal),
        ];
    }
}
