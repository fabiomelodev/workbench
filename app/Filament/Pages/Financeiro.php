<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BudgetStatsWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Financeiro: visão dos orçamentos das propostas (em aberto, contratado e
 * contratado no mês). Fica logo abaixo do Dashboard no menu.
 */
class Financeiro extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Financeiro';

    protected static ?int $navigationSort = -1;

    protected static ?string $title = 'Financeiro';

    protected function getHeaderWidgets(): array
    {
        return [
            BudgetStatsWidget::class,
        ];
    }
}
