<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ControlStatsWidget;
use App\Livewire\ProspectsNextTable;
use App\Livewire\ProspectsProgressTableWidget;
use App\Livewire\ProspectsTodayTable;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            ControlStatsWidget::class,
            ProspectsProgressTableWidget::class,
            ProspectsTodayTable::class,
            ProspectsNextTable::class
        ];
    }
}
