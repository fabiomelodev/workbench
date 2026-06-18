<?php

namespace App\Filament\Imports;

use App\Models\{City, Customer, Niche, Proposal, Prospect};
use Filament\Actions\Imports\{ImportColumn, Importer};
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Log;
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
                ->rules(['max:255']),
            ImportColumn::make('facebook')
                ->rules(['max:255']),
            ImportColumn::make('whatsapp')
                ->rules(['max:255'])
                ->castStateUsing(function (?string $state): ?string {
                    if (blank($state)) {
                        return null;
                    }

                    $onlyNumbers = preg_replace('/[^0-9]/', '', $state);

                    if (empty($onlyNumbers)) {
                        return null;
                    }

                    return 'https://wa.me/' . $onlyNumbers;
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

    public function resolveRecord(): Customer
    {
        return new Customer();
    }

    protected function afterCreate(): void
    {
        $proposalName = 'Proposta ' . $this->record->name;

        $proposalSlug = 'proposta-' . $this->record->slug;

        Log::info($proposalSlug);

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
        $body = 'Your customer import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
