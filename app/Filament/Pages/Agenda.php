<?php

namespace App\Filament\Pages;

use App\Models\Prospect;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Agenda de follow-ups: calendário mensal das prospecções pela data da próxima
 * ação (next_action). Cada dia lista as empresas a contatar, com navegação
 * entre meses. Prospecções encerradas (contratado/encerrado/sem resposta) não
 * aparecem.
 */
class Agenda extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $navigationLabel = 'Agenda';

    protected static ?int $navigationSort = -1;

    protected static ?string $title = 'Agenda de follow-ups';

    protected string $view = 'filament.pages.agenda';

    public int $year;

    public int $month;

    public function mount(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->subMonthNoOverflow();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->year, $this->month, 1)->addMonthNoOverflow();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function goToday(): void
    {
        $this->year = now()->year;
        $this->month = now()->month;
    }

    public function monthLabel(): string
    {
        return ucfirst(Carbon::create($this->year, $this->month, 1)
            ->locale('pt_BR')
            ->translatedFormat('F Y'));
    }

    /** Status que não geram follow-up (já encerrados). */
    protected function finishedStatuses(): array
    {
        return [Prospect::HIRED, Prospect::CLOSED, Prospect::NO_RESPONSE];
    }

    /**
     * Grade do mês: array de semanas, cada uma com 7 dias.
     * Cada dia: day, inMonth, isToday, isPast, prospects (Collection).
     */
    public function weeks(): array
    {
        $first = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $last = $first->copy()->endOfMonth();

        $byDate = Prospect::query()
            ->whereNotNull('next_action')
            ->whereDate('next_action', '>=', $first->toDateString())
            ->whereDate('next_action', '<=', $last->toDateString())
            ->whereNotIn('status', $this->finishedStatuses())
            ->with('proposal.customer')
            ->orderBy('next_action')
            ->get()
            ->groupBy(fn (Prospect $p) => $p->next_action->format('Y-m-d'));

        $gridStart = $first->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $last->copy()->endOfWeek(Carbon::SATURDAY);
        $today = Carbon::today();

        $weeks = [];
        $cursor = $gridStart->copy();

        while ($cursor <= $gridEnd) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $key = $cursor->format('Y-m-d');

                $week[] = [
                    'day' => $cursor->day,
                    'inMonth' => $cursor->month === $this->month,
                    'isToday' => $cursor->isSameDay($today),
                    'isPast' => $cursor->lt($today),
                    'prospects' => $byDate->get($key, collect()),
                ];

                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return $weeks;
    }

    public function followUpCount(): int
    {
        $first = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $last = $first->copy()->endOfMonth();

        return Prospect::query()
            ->whereNotNull('next_action')
            ->whereDate('next_action', '>=', $first->toDateString())
            ->whereDate('next_action', '<=', $last->toDateString())
            ->whereNotIn('status', $this->finishedStatuses())
            ->count();
    }
}
