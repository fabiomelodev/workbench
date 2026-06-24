<?php

namespace App\Filament\Resources\Prospects\Pages;

use App\Enums\LeadStatus;
use App\Filament\Resources\Prospects\ProspectResource;
use App\Helpers\FormatCurrency;
use App\Models\Prospect;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Wezlo\FilamentKanban\Concerns\HasKanbanBoard;
use Wezlo\FilamentKanban\KanbanBoard;

class ListProspects extends ListRecords
{
    use HasKanbanBoard;

    protected static string $resource = ProspectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function kanban(KanbanBoard $kanban): KanbanBoard
    {
        return $kanban
            ->enumColumn('status', LeadStatus::class)
            // ->cardTitle(fn($record) => $record->proposal->name)
            ->cardView('filament.pages.kanban.card')
            ->cardAction(
                Action::make('view')
                    ->slideOver()
                    ->schema([
                        TextEntry::make('proposal.name')
                            ->label('Proposta'),
                        TextEntry::make('proposal.amount')
                            ->label('Orçamento')
                            ->formatStateUsing(fn(string $state): string => FormatCurrency::getFormatCurrency($state)),
                        TextEntry::make('channel')
                            ->label('Canal Usado')
                            ->formatStateUsing(fn(string $state): string => Prospect::getChannel($state)),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn(string $state): string => Prospect::getStatus($state)),
                    ])
                    ->fillForm(fn($record) => $record->toArray())
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
            );
    }
}
