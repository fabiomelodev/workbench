<?php

namespace App\Filament\Imports;

use App\Models\{City, Customer, Niche, Proposal, Prospect};
use App\Services\PhoneNumberService;
use Filament\Actions\Imports\{ImportColumn, Importer};
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class CustomerImporter extends Importer
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label('Nome')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('instagram')
                ->rules(['max:255'])
                ->castStateUsing(fn(?string $state): ?string => filled($state) ? trim($state) : null),
            ImportColumn::make('facebook')
                ->rules(['max:255']),
            ImportColumn::make('phone')
                ->label('Telefone / WhatsApp')
                ->rules(['max:255'])
                ->castStateUsing(function (?string $state): ?string {
                    if (blank($state)) {
                        return null;
                    }

                    // Guarda apenas os dígitos; o Customer classifica (celular/fixo)
                    // e gera o link de WhatsApp automaticamente ao salvar.
                    $onlyNumbers = preg_replace('/[^0-9]/', '', $state);

                    return $onlyNumbers ?: null;
                }),
            ImportColumn::make('email')
                ->rules(['email', 'max:255']),
            ImportColumn::make('website')
                ->rules(['max:255']),
            ImportColumn::make('city')
                ->relationship(resolveUsing: function (string $state): ?City {
                    return City::query()
                        ->where('name', 'LIKE', '%' . $state . '%')
                        ->orWhere('id', 1)
                        ->first();
                })
                ->rules(['required']),
            ImportColumn::make('niche')
                ->relationship(resolveUsing: function (string $state): ?Niche {
                    return Niche::query()
                        ->where('name', 'LIKE', '%' . $state . '%')
                        ->orWhere('id', 1)
                        ->first();
                })
                ->rules(['required']),
        ];
    }

    /**
     * Evita clientes duplicados: se já existe um cliente com o mesmo telefone,
     * atualiza-o (mescla) em vez de criar um novo.
     */
    public function resolveRecord(): Customer
    {
        $raw = $this->data['phone'] ?? null;

        if (filled($raw)) {
            $parsed = app(PhoneNumberService::class)->parse($raw);
            $digits = $parsed['digits'] ?? preg_replace('/\D+/', '', (string) $raw);

            if (filled($digits)) {
                $existing = Customer::query()
                    ->where('phone', $digits)
                    ->orWhere('whatsapp', 'LIKE', '%' . $digits . '%')
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }
        }

        return new Customer();
    }

    /**
     * Em re-importações de um cliente existente, não sobrescreve dados já
     * preenchidos: o import apenas preenche as lacunas (enriquece).
     */
    protected function beforeSave(): void
    {
        if (! $this->record->exists) {
            return;
        }

        foreach (['name', 'instagram', 'facebook', 'email', 'website', 'phone', 'city_id', 'niche_id'] as $field) {
            if (filled($this->record->getOriginal($field))) {
                $this->record->{$field} = $this->record->getOriginal($field);
            }
        }
    }

    protected function afterCreate(): void
    {
        $proposalName = 'Proposta ' . $this->record->name;

        $proposalSlug = 'proposta-' . $this->record->slug;

        $proposal = Proposal::updateOrCreate(
            ['slug' => $proposalSlug],
            [
                'name' => $proposalName,
                'slug' => $proposalSlug,
                'amount' => 0,
                'website' => '',
                'status' => 'active',
                'customer_id' => $this->record->id
            ]
        );

        Prospect::create([
            'proposal_id' => $proposal->id,
        ]);

    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'A importação de clientes foi concluída: ' . Number::format($import->successful_rows) . ' ' . str('linha')->plural($import->successful_rows) . ' processada(s).';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('linha')->plural($failedRowsCount) . ' falhou(aram).';
        }

        return $body;
    }
}
