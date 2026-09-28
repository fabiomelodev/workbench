<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\ChargeService;
use Illuminate\Console\Command;

class GenerateSubscriptionPayments extends Command
{
    protected $signature = 'transactions:generate-subscriptions';

    protected $description = 'Gera as próximas mensalidades das assinaturas ativas (mantém sempre a próxima mensalidade prevista)';

    public function handle(ChargeService $charges): int
    {
        $created = 0;

        Transaction::query()
            ->notCanceled()
            ->whereNull('installments')
            ->each(function (Transaction $transaction) use ($charges, &$created) {
                $created += $charges->generateSubscriptionPayments($transaction);
            });

        $this->info("Mensalidades geradas: {$created}.");

        return self::SUCCESS;
    }
}
