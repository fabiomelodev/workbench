<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ControlStatsWidget;
use App\Livewire\KanbanDashboardWidget;
use App\Livewire\ProspectsToWorkTable;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            ControlStatsWidget::class,
            ProspectsToWorkTable::class,
            KanbanDashboardWidget::class,
        ];
    }
}
