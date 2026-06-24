<?php

namespace App\Filament\Resources\Prospects\Pages;

use Illuminate\Contracts\Support\Htmlable;

/**
 * Versão do board de prospecção para embutir no Dashboard: usa o mesmo render do
 * board, mas suprime o "chrome" da página (breadcrumb "Prospecções > Board",
 * título "Prospecções" e o botão "Criar Prospecção"). Com o heading vazio, o
 * Filament não renderiza o cabeçalho — então não sobra espaço em branco.
 * A página padrão /admin/prospects (ListProspects) mantém o cabeçalho completo.
 */
class DashboardKanban extends ListProspects
{
    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
