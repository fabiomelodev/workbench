<?php

namespace App\Filament\Pages;

use App\Models\Niche;
use App\Models\Prospect;
use App\Models\ProspectAttempt;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Relatórios de prospecção. Foco principal: taxa de resposta por canal —
 * usando o desfecho registrado em cada tentativa. A taxa considera apenas
 * tentativas com desfecho informado e exclui "número errado" do denominador.
 */
class Relatorios extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Relatórios';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Relatórios';

    protected string $view = 'filament.pages.relatorios';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?int $nicheId = null;

    protected ?Collection $cachedAttempts = null;

    public function mount(): void
    {
        $this->startDate = now()->subMonths(6)->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
    }

    public function updated(): void
    {
        // Qualquer mudança de filtro invalida o cache da consulta.
        $this->cachedAttempts = null;
    }

    public function nicheOptions(): array
    {
        return Niche::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    /** Tentativas no período/nicho selecionados (memoizado por request). */
    protected function attempts(): Collection
    {
        return $this->cachedAttempts ??= ProspectAttempt::query()
            ->when($this->startDate, fn (Builder $q) => $q->whereDate('attempted_at', '>=', $this->startDate))
            ->when($this->endDate, fn (Builder $q) => $q->whereDate('attempted_at', '<=', $this->endDate))
            ->when($this->nicheId, fn (Builder $q) => $q->whereHas(
                'prospect.proposal.customer',
                fn (Builder $c) => $c->where('niche_id', $this->nicheId),
            ))
            ->get(['channel', 'outcome']);
    }

    public function kpis(): array
    {
        $attempts = $this->attempts();
        $response = ProspectAttempt::responseOutcomes();
        $invalid = ProspectAttempt::invalidOutcomes();

        $valid = $attempts->filter(fn ($a) => filled($a->outcome) && ! in_array($a->outcome, $invalid, true))->count();
        $responses = $attempts->filter(fn ($a) => in_array($a->outcome, $response, true))->count();

        return [
            'total' => $attempts->count(),
            'responses' => $responses,
            'rate' => $valid > 0 ? (int) round($responses / $valid * 100) : 0,
            'meetings' => $attempts->where('outcome', ProspectAttempt::OUTCOME_MEETING)->count(),
            'closed' => $attempts->where('outcome', ProspectAttempt::OUTCOME_CLOSED)->count(),
        ];
    }

    /** Taxa de resposta por canal, ordenada da maior para a menor. */
    public function channelStats(): array
    {
        $attempts = $this->attempts();
        $response = ProspectAttempt::responseOutcomes();
        $invalid = ProspectAttempt::invalidOutcomes();

        $rows = [];

        foreach (Prospect::getTypeChannels() as $key => $label) {
            $channel = $attempts->where('channel', $key);
            $valid = $channel->filter(fn ($a) => filled($a->outcome) && ! in_array($a->outcome, $invalid, true))->count();
            $responses = $channel->filter(fn ($a) => in_array($a->outcome, $response, true))->count();

            $rows[] = [
                'label' => $label,
                'total' => $channel->count(),
                'valid' => $valid,
                'responses' => $responses,
                'rate' => $valid > 0 ? (int) round($responses / $valid * 100) : 0,
                'hasData' => $valid > 0,
            ];
        }

        usort($rows, fn ($a, $b) => [$b['rate'], $b['total']] <=> [$a['rate'], $a['total']]);

        return $rows;
    }

    /** Quantas tentativas de cada desfecho (inclui "não informado"). */
    public function outcomeDistribution(): array
    {
        $attempts = $this->attempts();
        $rows = [];

        foreach (ProspectAttempt::getOutcomes() as $key => $label) {
            $rows[] = ['label' => $label, 'count' => $attempts->where('outcome', $key)->count()];
        }

        $unknown = $attempts->whereNull('outcome')->count();

        if ($unknown > 0) {
            $rows[] = ['label' => 'Não informado', 'count' => $unknown];
        }

        return $rows;
    }
}
