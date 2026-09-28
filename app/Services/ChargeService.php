<?php

namespace App\Services;

use App\Models\{Proposal, Transaction};
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cria cobranças (Transaction) com as parcelas (Payment) já previstas.
 *
 * - Orçamento fechado: N parcelas mensais a partir do 1º vencimento; a última
 *   absorve a diferença de centavos para a soma bater com o total.
 * - Assinatura: parcelas mensais geradas sob demanda, sempre mantendo a
 *   próxima mensalidade prevista (ver generateSubscriptionPayments).
 */
class ChargeService
{
    public function create(
        Proposal $proposal,
        float $amount,
        ?int $installments,
        CarbonInterface $firstDueDate,
        ?string $notes = null,
    ): Transaction {
        return DB::transaction(function () use ($proposal, $amount, $installments, $firstDueDate, $notes) {
            $transaction = $proposal->transactions()->create([
                'amount' => $amount,
                'installments' => $installments,
                'status' => Transaction::PENDING,
                'notes' => $notes,
            ]);

            if ($installments === null) {
                $this->addPayment($transaction, 1, $amount, $firstDueDate);
                $this->generateSubscriptionPayments($transaction);
            } else {
                foreach (static::splitInstallments($amount, $installments) as $index => $value) {
                    $this->addPayment($transaction, $index + 1, $value, $firstDueDate->copy()->addMonthsNoOverflow($index));
                }
            }

            return $transaction->refresh();
        });
    }

    /**
     * Divide o total em parcelas em centavos: 1000 / 3 = 333,33 + 333,33 + 333,34.
     *
     * @return list<float>
     */
    public static function splitInstallments(float $amount, int $installments): array
    {
        $installments = max(1, $installments);
        $cents = (int) round($amount * 100);
        $base = intdiv($cents, $installments);

        $values = array_fill(0, $installments, $base);
        $values[$installments - 1] += $cents - ($base * $installments);

        return array_map(fn (int $value) => round($value / 100, 2), $values);
    }

    /**
     * Assinatura: cria as mensalidades que faltam até existir uma parcela com
     * vencimento depois de $reference (padrão: hoje), ou seja, sempre há a
     * próxima mensalidade prevista. Idempotente.
     *
     * @return int Quantidade de parcelas criadas.
     */
    public function generateSubscriptionPayments(Transaction $transaction, ?CarbonInterface $reference = null): int
    {
        if (! $transaction->isSubscription() || $transaction->isCanceled()) {
            return 0;
        }

        $reference ??= today();

        $first = $transaction->payments()->first();
        $last = $transaction->payments()->reorder('number', 'desc')->first();

        if (! $first || ! $last) {
            return 0;
        }

        $created = 0;
        $number = $last->number;
        $dueDate = Carbon::parse($last->due_date);

        while ($dueDate->lte($reference)) {
            // Sempre a partir do 1º vencimento, para não "escorregar" o dia (31 → 28 → 28...).
            $dueDate = Carbon::parse($first->due_date)->addMonthsNoOverflow($number);
            $number++;

            $this->addPayment($transaction, $number, (float) $transaction->amount, $dueDate);
            $created++;
        }

        return $created;
    }

    protected function addPayment(Transaction $transaction, int $number, float $amount, CarbonInterface $dueDate): void
    {
        $transaction->payments()->create([
            'number' => $number,
            'amount' => $amount,
            'due_date' => $dueDate->toDateString(),
        ]);
    }
}
