<?php

namespace Tests\Feature;

use App\Filament\Pages\Financeiro;
use App\Filament\Resources\Proposals\Pages\ListProposals;
use App\Filament\Resources\Transactions\Pages\{ListTransactions, ViewTransaction};
use App\Filament\Resources\Transactions\RelationManagers\PaymentsRelationManager;
use App\Filament\Widgets\{ReceivablesStatsWidget, UpcomingPaymentsTable};
use App\Models\{Customer, Proposal, Transaction, User};
use App\Services\ChargeService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceiroPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['email' => 'teste@singletemas.com.br']));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
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

    public function test_generate_charge_from_proposals_table(): void
    {
        $proposal = $this->proposal();

        Livewire::test(ListProposals::class)
            ->callAction(TestAction::make('generateCharge')->table($proposal), [
                'amount' => 890,
                'installments' => 4,
                'first_due_date' => '2026-10-10',
            ])
            ->assertHasNoFormErrors();

        $transaction = $proposal->activeTransaction();
        $this->assertNotNull($transaction);
        $this->assertSame(4, $transaction->payments()->count());

        // Com cobrança vigente, a ação some da linha.
        Livewire::test(ListProposals::class)
            ->assertActionHidden(TestAction::make('generateCharge')->table($proposal));
    }

    public function test_generate_subscription_charge_from_transactions_header(): void
    {
        $proposal = $this->proposal('signature');

        Livewire::test(ListTransactions::class)
            ->callAction('generateCharge', [
                'proposal_id' => $proposal->id,
                'amount' => 78.90,
                'first_due_date' => today()->toDateString(),
            ])
            ->assertHasNoFormErrors();

        $transaction = $proposal->activeTransaction();
        $this->assertTrue($transaction->isSubscription());
        $this->assertSame(2, $transaction->payments()->count());
    }

    public function test_pages_render_and_mark_paid_settles_the_charge(): void
    {
        $transaction = app(ChargeService::class)->create($this->proposal(), 890, 2, Carbon::parse('2026-10-10'));
        [$first, $second] = $transaction->payments->all();

        Livewire::test(ListTransactions::class)->assertCanSeeTableRecords([$transaction]);
        Livewire::test(ViewTransaction::class, ['record' => $transaction->getRouteKey()])->assertSuccessful();
        Livewire::test(Financeiro::class)->assertSuccessful();
        Livewire::test(ReceivablesStatsWidget::class)->assertSuccessful();

        Livewire::test(UpcomingPaymentsTable::class)
            ->assertCanSeeTableRecords([$first, $second])
            ->callAction(TestAction::make('markPaid')->table($first), ['paid_at' => '2026-10-10', 'method' => 'pix']);

        Livewire::test(PaymentsRelationManager::class, ['ownerRecord' => $transaction, 'pageClass' => ViewTransaction::class])
            ->assertCanSeeTableRecords([$first, $second])
            ->assertActionHidden(TestAction::make('markPaid')->table($first->refresh()))
            ->callAction(TestAction::make('markPaid')->table($second), ['paid_at' => '2026-11-10', 'method' => 'boleto']);

        $this->assertSame(Transaction::PAID, $transaction->refresh()->status);

        Livewire::test(ViewTransaction::class, ['record' => $transaction->getRouteKey()])
            ->callAction('cancel');
        $this->assertSame(Transaction::CANCELED, $transaction->refresh()->status);
    }
}
