<?php

namespace Tests\Feature;

use App\Filament\Pages\Financeiro;
use App\Filament\Resources\Proposals\Pages\ListProposals;
use App\Filament\Resources\Transactions\Pages\{ListTransactions, ViewTransaction};
use App\Filament\Resources\Transactions\RelationManagers\PaymentsRelationManager;
use App\Filament\Widgets\{ReceivablesStatsWidget, UpcomingPaymentsTable};
use App\Models\{Customer, Payment, Proposal, Transaction, User};
use App\Services\ChargeService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
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

    public function test_receipts_can_be_attached_when_marking_paid_and_after_payment(): void
    {
        Storage::fake(Payment::RECEIPTS_DISK);

        $transaction = app(ChargeService::class)->create($this->proposal(), 890, 2, Carbon::parse('2026-10-10'));
        [$first, $second] = $transaction->payments->all();
        $manager = fn () => Livewire::test(PaymentsRelationManager::class, ['ownerRecord' => $transaction, 'pageClass' => ViewTransaction::class]);

        // Junto com o pagamento.
        $manager()->callAction(TestAction::make('markPaid')->table($first), [
            'paid_at' => '2026-10-10',
            'method' => 'pix',
            'receipts' => [UploadedFile::fake()->image('pix-1.jpg')],
        ])->assertHasNoFormErrors();

        $this->assertSame(1, $first->refresh()->receiptsCount());
        Storage::disk(Payment::RECEIPTS_DISK)->assertExists($first->receipts[0]);

        // Parcela paga sem comprovante: anexa depois (o caso do "esqueci").
        $second->update(['paid_at' => '2026-11-10', 'method' => 'pix']);

        $manager()
            ->assertActionVisible(TestAction::make('receipts')->table($second))
            ->callAction(TestAction::make('receipts')->table($second->refresh()), [
                'receipts' => [UploadedFile::fake()->image('pix-2.png'), UploadedFile::fake()->create('pix-2.pdf', 100, 'application/pdf')],
            ])->assertHasNoFormErrors();

        $second->refresh();
        $this->assertSame(2, $second->receiptsCount());
        $this->assertTrue($second->isPaid());

        // Remover um comprovante apaga o arquivo do disco.
        [$kept, $removed] = $second->receipts;
        $second->update(['receipts' => [$kept]]);
        Storage::disk(Payment::RECEIPTS_DISK)->assertMissing($removed);
        Storage::disk(Payment::RECEIPTS_DISK)->assertExists($kept);

        // Excluir a cobrança apaga os comprovantes de todas as parcelas.
        $firstReceipt = $first->receipts[0];
        $transaction->delete();
        Storage::disk(Payment::RECEIPTS_DISK)->assertMissing($firstReceipt);
        Storage::disk(Payment::RECEIPTS_DISK)->assertMissing($kept);
    }

    public function test_receipts_reject_non_image_files(): void
    {
        Storage::fake(Payment::RECEIPTS_DISK);

        $transaction = app(ChargeService::class)->create($this->proposal(), 890, 1, Carbon::parse('2026-10-10'));

        Livewire::test(PaymentsRelationManager::class, ['ownerRecord' => $transaction, 'pageClass' => ViewTransaction::class])
            ->callAction(TestAction::make('receipts')->table($transaction->payments->first()), [
                'receipts' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')],
            ])
            ->assertHasFormErrors(['receipts']);
    }
}
