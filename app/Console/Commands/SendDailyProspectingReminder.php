<?php

namespace App\Console\Commands;

use App\Models\Prospect;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Lembrete diário de prospecção: calcula quantas prospecções estão atrasadas e
 * quantas são para hoje e envia uma notificação no sino do painel para cada
 * usuário. Agendado em routes/console.php (dias úteis, de manhã).
 */
class SendDailyProspectingReminder extends Command
{
    protected $signature = 'prospects:daily-reminder';

    protected $description = 'Envia o lembrete diário de prospecção (atrasados + para hoje) no painel';

    public function handle(): int
    {
        $finished = [Prospect::HIRED, Prospect::CLOSED, Prospect::NO_RESPONSE];

        $overdue = Prospect::query()
            ->whereDate('next_action', '<', now())
            ->whereNotIn('status', $finished)
            ->count();

        $today = Prospect::query()
            ->where(fn (Builder $q) => $q->whereDate('next_action', now())->orWhereDate('last_action', now()))
            ->count();

        if ($overdue === 0 && $today === 0) {
            $this->info('Nada para prospectar hoje — nenhum lembrete enviado.');

            return self::SUCCESS;
        }

        $parts = [];

        if ($today > 0) {
            $parts[] = "{$today} para hoje";
        }

        if ($overdue > 0) {
            $parts[] = "{$overdue} atrasada(s)";
        }

        $body = 'Você tem ' . implode(' e ', $parts) . '. Bora prospectar! 🚀';

        $users = User::all();

        foreach ($users as $user) {
            Notification::make()
                ->title('Prospecção do dia')
                ->icon(Heroicon::OutlinedBell)
                ->iconColor('warning')
                ->body($body)
                ->actions([
                    Action::make('abrir')
                        ->label('Abrir painel')
                        ->url(route('filament.admin.pages.dashboard'))
                        ->markAsRead(),
                ])
                ->sendToDatabase($user);
        }

        $this->info("Lembrete enviado para {$users->count()} usuário(s): {$body}");

        return self::SUCCESS;
    }
}
