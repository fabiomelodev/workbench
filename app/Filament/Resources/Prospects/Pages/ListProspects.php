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
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
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

    /**
     * Mostra o quadro Kanban e, logo abaixo, a tabela padrão do Filament
     * (com busca, filtros, ordenação e paginação). O trait HasKanbanBoard
     * normalmente exibe só o board; aqui acrescentamos a tabela.
     */
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make($this->getKanbanBoard()->getBoardView()),
            EmbeddedTable::make(),
        ]);
    }

    public function kanban(KanbanBoard $kanban): KanbanBoard
    {
        return $kanban
            ->enumColumn('status', LeadStatus::class)
            ->columnSummary(function ($records, $column) {
                return match ($column->value) {
                    LeadStatus::NEW ->value => 'Quando não fez nenhuma tentativa de contato.',
                    LeadStatus::ON_HOLD->value => 'O contato foi feito, há interesse, mas o lead pediu para adiar a negociação por razões de timing/orçamento.',
                    LeadStatus::INTOUCH->value => 'O cliente respondeu! A conversa está acontecendo agora em tempo real.',
                    LeadStatus::AWAITING_RETURN->value => 'Você iniciou o contato (enviou mensagem/e-mail) e a bola está com o cliente.',
                    LeadStatus::PASSED_DEPARTMENT->value => 'A pessoa com quem você falou não decide, mas te passou o contato do decisor.',
                    LeadStatus::SCHEDULED_MEETING->value => 'O lead aceitou bater um papo',
                    LeadStatus::HIRED->value => 'Contrato assinado, pix feito ou serviço iniciado. Sucesso total!',
                    LeadStatus::CLOSED->value => 'O cliente recusou ativamente o serviço',
                    LeadStatus::NO_RESPONSE->value => 'Você tentou contato 3 ou 4 vezes (fluxo de cadência) em dias diferentes e foi totalmente ignorado (vácuo).',
                    default => ''
                };
            })
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
                        TextEntry::make('attempts_count')
                            ->label('Tentativas')
                            ->badge()
                            ->state(fn(Prospect $record): string => $record->attempts()->count() . '/' . Prospect::MAX_ATTEMPTS)
                            ->color(fn(Prospect $record): string => $record->attempts()->count() >= Prospect::MAX_ATTEMPTS ? 'danger' : 'gray'),
                    ])
                    ->fillForm(fn($record) => $record->toArray())
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
            );
    }
}
