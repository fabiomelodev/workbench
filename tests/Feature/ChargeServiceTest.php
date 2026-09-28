<?php

namespace Tests\Feature;

use App\Models\{Customer, Payment, Proposal, Transaction};
use App\Services\ChargeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ChargeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function proposal(string $type = 'closed_budget'): Proposal
    {
        $customer = Customer::create(['name' => 'Cliente ' . uniqid()]);

        return Proposal::create([
            'name' => 'Proposta ' . $customer->name,
            'customer_id' => $customer->id,
            'amount' => 890,
            'type' => $type,
        ]);
    }

    public function test_split_installments_last_one_absorbs_the_cents(): void
    {
        $this->assertSame([333.33, 333.33, 333.34], ChargeService::splitInstallments(1000, 3));
        $this->assertSame([222.5, 222.5, 222.5, 222.5], ChargeService::splitInstallments(890, 4));
        $this->assertSame([890.0], ChargeService::splitInstallments(890, 1));
    }

    public function test_closed_budget_creates_monthly_installments(): void
    {
        $transaction = app(ChargeService::class)->create($this->proposal(), 890, 4, Carbon::parse('2026-10-10'));

        $payments = $transaction->payments;

        $this->assertCount(4, $payments);
        $this->assertSame([1, 2, 3, 4], $payments->pluck('number')->all());
        $this->assertSame(
            ['2026-10-10', '2026-11-10', '2026-12-10', '2027-01-10'],
            $payments->map(fn (Payment $p) => $p->due_date->toDateString())->all(),
        );
        $this->assertEquals(890, $payments->sum('amount'));
        $this->assertSame(Transaction::PENDING, $transaction->status);
    }

    public function test_transaction_is_settled_only_when_all_installments_are_paid_and_reopens_on_undo(): void
    {
        $transaction = app(ChargeService::class)->create($this->proposal(), 890, 2, Carbon::parse('2026-10-10'));
        [$first, $second] = $transaction->payments->all();

        $first->update(['paid_at' => '2026-10-10', 'method' => 'pix']);
        $this->assertSame(Transaction::PENDING, $transaction->refresh()->status);

        $second->update(['paid_at' => '2026-11-12', 'method' => 'pix']);
        $transaction->refresh();
        $this->assertSame(Transaction::PAID, $transaction->status);
        $this->assertSame('2026-11-12', $transaction->paid_at->toDateString());

        $second->update(['paid_at' => null]);
        $transaction->refresh();
        $this->assertSame(Transaction::PENDING, $transaction->status);
        $this->assertNull($transaction->paid_at);
    }

    public function test_subscription_keeps_one_installment_ahead_and_is_idempotent(): void
    {
        Carbon::setTestNow('2026-10-05');
        $charges = app(ChargeService::class);

        $transaction = $charges->create($this->proposal('signature'), 78.90, null, Carbon::parse('2026-10-05'));

        $this->assertTrue($transaction->isSubscription());
        $this->assertSame(['2026-10-05', '2026-11-05'], $transaction->payments->map(fn (Payment $p) => $p->due_date->toDateString())->all());
        $this->assertSame(0, $charges->generateSubscriptionPayments($transaction));

        // Três meses depois o comando diário completa as mensalidades.
        Carbon::setTestNow('2027-01-06');
        $this->assertSame(3, $charges->generateSubscriptionPayments($transaction));
        $this->assertSame('2027-02-05', $transaction->payments()->reorder('number', 'desc')->first()->due_date->toDateString());

        // Assinatura nunca quita, mesmo com todas as parcelas pagas.
        $transaction->payments->each->update(['paid_at' => '2026-10-05']);
        $this->assertSame(Transaction::PENDING, $transaction->refresh()->status);
    }

    public function test_subscription_due_day_does_not_drift_after_short_months(): void
    {
        Carbon::setTestNow('2026-01-31');
        $charges = app(ChargeService::class);
        $transaction = $charges->create($this->proposal('signature'), 50, null, Carbon::parse('2026-01-31'));

        Carbon::setTestNow('2026-03-31');
        $charges->generateSubscriptionPayments($transaction);

        $this->assertSame(
            ['2026-01-31', '2026-02-28', '2026-03-31', '2026-04-30'],
            $transaction->payments()->get()->map(fn (Payment $p) => $p->due_date->toDateString())->all(),
        );
    }

    public function test_canceled_transaction_is_ignored(): void
    {
        $charges = app(ChargeService::class);
        $transaction = $charges->create($this->proposal('signature'), 78.90, null, today());
        $transaction->update(['status' => Transaction::CANCELED]);

        $this->assertSame(0, $charges->generateSubscriptionPayments($transaction, today()->addYear()));
        $this->assertSame(0, Payment::query()->open()->count());

        // Mudar uma parcela não "reabre" a cobrança cancelada.
        $transaction->payments()->first()->update(['paid_at' => today()]);
        $this->assertSame(Transaction::CANCELED, $transaction->refresh()->status);
        $this->assertNull($transaction->proposal->activeTransaction());
    }
}
