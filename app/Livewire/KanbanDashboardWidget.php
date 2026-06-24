<?php

namespace App\Livewire;

use Filament\Widgets\Widget;

class KanbanDashboardWidget extends Widget
{
    protected string $view = 'livewire.kanban-dashboard-widget';

    protected int|string|array $columnSpan = 'full';
}
