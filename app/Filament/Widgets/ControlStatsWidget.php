<?php

namespace App\Filament\Widgets;

use App\Models\{Customer, Proposal, Prospect};
use App\Services\PhoneNumberService;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class ControlStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $customersTotal = Customer::query()->active()->count();
        $proposalsTotal = Proposal::query()->active()->count();
        $prospectsTotal = Prospect::query()->whereIn('status', Prospect::getTypeStatusProgress())->count();

        // Qualidade dos dados entre os clientes ativos (o que ainda precisa enriquecer).
        $mobile = Customer::query()->active()->where('phone_type', PhoneNumberService::MOBILE)->count();
        $noWhatsapp = Customer::query()->active()
            ->where(fn(Builder $q) => $q->where('phone_type', '!=', PhoneNumberService::MOBILE)->orWhereNull('phone_type'))
            ->count();
        $noInstagram = Customer::query()->active()
            ->where(fn(Builder $q) => $q->whereNull('instagram')->orWhere('instagram', ''))
            ->count();
        $noWebsite = Customer::query()->active()
            ->where(fn(Builder $q) => $q->whereNull('website')->orWhere('website', ''))
            ->count();

        return [
            Stat::make('Clientes Ativos', $customersTotal),
            Stat::make('Propostas Ativas', $proposalsTotal),
            Stat::make('Prospecções em Andamento', $prospectsTotal),
            Stat::make('WhatsApp válido (celular)', $mobile)
                ->description('Prontos para abordagem')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success'),
            Stat::make('Sem WhatsApp válido', $noWhatsapp)
                ->description('Fixo, inválido ou sem número')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger'),
            Stat::make('Faltando Instagram', $noInstagram)
                ->color('warning'),
            Stat::make('Faltando site', $noWebsite)
                ->color('warning'),
        ];
    }
}
