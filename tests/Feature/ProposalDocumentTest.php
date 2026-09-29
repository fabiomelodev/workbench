<?php

namespace Tests\Feature;

use App\Filament\Resources\Proposals\Pages\EditProposal;
use App\Models\{Customer, Payment, Proposal, User};
use App\Services\ChargeService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProposalDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Proposal::DOCUMENT_DISK);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    protected function proposal(): Proposal
    {
        $customer = Customer::create(['name' => 'Cliente ' . uniqid()]);

        return Proposal::create([
            'name' => 'Proposta ' . $customer->name,
            'customer_id' => $customer->id,
            'amount' => 890,
            'type' => 'closed_budget',
        ]);
    }

    protected function admin(): User
    {
        return User::factory()->create(['email' => 'teste@singletemas.com.br']);
    }

    public function test_pdf_is_uploaded_on_edit_and_shown_in_the_viewer(): void
    {
        $this->actingAs($this->admin());
        $proposal = $this->proposal();

        Livewire::test(EditProposal::class, ['record' => $proposal->getRouteKey()])
            ->assertSee('Nenhuma proposta anexada ainda')
            ->fillForm(['document' => UploadedFile::fake()->create('proposta.pdf', 200, 'application/pdf')])
            ->call('save')
            ->assertHasNoFormErrors();

        $proposal->refresh();
        $this->assertTrue($proposal->hasDocument());
        Storage::disk(Proposal::DOCUMENT_DISK)->assertExists($proposal->document);

        Livewire::test(EditProposal::class, ['record' => $proposal->getRouteKey()])
            ->assertSee(route('proposals.document', $proposal), escape: false)
            ->assertSee('<iframe', escape: false);
    }

    public function test_non_pdf_files_are_rejected(): void
    {
        $this->actingAs($this->admin());
        $proposal = $this->proposal();

        Livewire::test(EditProposal::class, ['record' => $proposal->getRouteKey()])
            ->fillForm(['document' => UploadedFile::fake()->create('proposta.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')])
            ->call('save')
            ->assertHasFormErrors(['document']);
    }

    public function test_document_route_serves_inline_only_for_panel_users(): void
    {
        $proposal = $this->proposal();
        $proposal->update(['document' => UploadedFile::fake()->create('p.pdf', 10, 'application/pdf')->store('proposals', Proposal::DOCUMENT_DISK)]);
        $url = route('proposals.document', $proposal);

        $this->get($url)->assertForbidden();

        $this->actingAs(User::factory()->create(['email' => 'intruso@gmail.com']))
            ->get($url)->assertForbidden();

        $response = $this->actingAs($this->admin())->get($url);
        $response->assertOk();
        $this->assertStringStartsWith('inline', $response->headers->get('Content-Disposition'));
        $this->assertNull($response->headers->get('Content-Security-Policy'));

        $this->get($url . '?download=1')->assertHeader('Content-Disposition', 'attachment; filename=' . \Illuminate\Support\Str::slug($proposal->name) . '.pdf');
    }

    public function test_replacing_or_deleting_removes_the_old_files(): void
    {
        $proposal = $this->proposal();
        $disk = Storage::disk(Proposal::DOCUMENT_DISK);

        $first = UploadedFile::fake()->create('v1.pdf', 10, 'application/pdf')->store('proposals', Proposal::DOCUMENT_DISK);
        $proposal->update(['document' => $first]);

        $second = UploadedFile::fake()->create('v2.pdf', 10, 'application/pdf')->store('proposals', Proposal::DOCUMENT_DISK);
        $proposal->update(['document' => $second]);
        $disk->assertMissing($first);
        $disk->assertExists($second);

        // Excluir o cliente (cascade no banco) também limpa PDF e comprovantes.
        $transaction = app(ChargeService::class)->create($proposal, 890, 1, today());
        $receipt = UploadedFile::fake()->image('pix.jpg')->store('receipts', Payment::RECEIPTS_DISK);
        $transaction->payments->first()->update(['receipts' => [$receipt]]);

        $proposal->customer->delete();

        $disk->assertMissing($second);
        $disk->assertMissing($receipt);
        $this->assertDatabaseCount('proposals', 0);
    }
}
