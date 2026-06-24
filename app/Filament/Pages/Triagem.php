<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Services\PhoneNumberService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Modo Triagem: percorre, um a um, os leads que ainda precisam de
 * enriquecimento (sem WhatsApp válido, sem Instagram ou sem site), com os
 * botões de 1 clique da Central de Contato + campos para colar e "Salvar/Pular".
 * Pensado para limpar o backlog rapidamente.
 */
class Triagem extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Clientes';

    protected static ?string $navigationLabel = 'Modo Triagem';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Modo Triagem';

    protected string $view = 'filament.pages.triagem';

    public ?int $currentId = null;

    /** Ids já tratados/pulados nesta sessão (não voltam a aparecer). */
    public array $skipped = [];

    public ?string $phone = null;
    public ?string $instagram = null;
    public ?string $website = null;
    public ?string $email = null;

    public function mount(): void
    {
        $this->loadNext();
    }

    /** Clientes ativos com pelo menos uma lacuna: WhatsApp, Instagram ou site. */
    protected static function pendingQuery(): Builder
    {
        return Customer::query()->active()->where(function (Builder $q) {
            $q->where('phone_type', '!=', PhoneNumberService::MOBILE)
                ->orWhereNull('phone_type')
                ->orWhereNull('instagram')->orWhere('instagram', '')
                ->orWhereNull('website')->orWhere('website', '');
        });
    }

    public function currentCustomer(): ?Customer
    {
        return $this->currentId ? Customer::find($this->currentId) : null;
    }

    public function remainingCount(): int
    {
        return static::pendingQuery()
            ->when($this->skipped, fn (Builder $q) => $q->whereNotIn('id', $this->skipped))
            ->count();
    }

    protected function loadNext(): void
    {
        $next = static::pendingQuery()
            ->when($this->skipped, fn (Builder $q) => $q->whereNotIn('id', $this->skipped))
            ->orderBy('id')
            ->first();

        $this->currentId = $next?->id;
        $this->phone = $next?->phone;
        $this->instagram = $next?->instagram;
        $this->website = $next?->website;
        $this->email = $next?->email;
    }

    public function save(): void
    {
        $customer = $this->currentCustomer();

        if (! $customer) {
            return;
        }

        $customer->update([
            'phone' => $this->phone,
            'instagram' => $this->instagram,
            'website' => $this->website,
            'email' => $this->email,
        ]);

        Notification::make()
            ->title('Salvo! Indo para o próximo lead.')
            ->success()
            ->send();

        $this->skipped[] = $customer->id;
        $this->loadNext();
    }

    public function skip(): void
    {
        if ($this->currentId) {
            $this->skipped[] = $this->currentId;
        }

        $this->loadNext();
    }

    public function restart(): void
    {
        $this->skipped = [];
        $this->loadNext();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::pendingQuery()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
