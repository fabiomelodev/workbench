<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\PhoneNumberService;
use Illuminate\Console\Command;

class ClassifyCustomerPhones extends Command
{
    protected $signature = 'customers:classify-phones';

    protected $description = 'Reprocessa o telefone de todos os clientes e classifica em celular/fixo/inválido';

    public function handle(): int
    {
        $total = Customer::query()->count();

        if ($total === 0) {
            $this->info('Nenhum cliente para processar.');

            return self::SUCCESS;
        }

        $counts = [
            PhoneNumberService::MOBILE => 0,
            PhoneNumberService::LANDLINE => 0,
            PhoneNumberService::OTHER => 0,
            PhoneNumberService::INVALID => 0,
            'sem_numero' => 0,
        ];

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Customer::query()->chunkById(200, function ($customers) use (&$counts, $bar) {
            foreach ($customers as $customer) {
                $customer->syncPhoneClassification();
                $customer->saveQuietly();

                $counts[$customer->phone_type ?? 'sem_numero']++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info('Classificação concluída:');
        $this->table(
            ['Tipo', 'Quantidade'],
            [
                ['📱 Celular (WhatsApp)', $counts[PhoneNumberService::MOBILE]],
                ['☎️  Fixo', $counts[PhoneNumberService::LANDLINE]],
                ['Outro', $counts[PhoneNumberService::OTHER]],
                ['⚠️  Inválido', $counts[PhoneNumberService::INVALID]],
                ['Sem número', $counts['sem_numero']],
            ]
        );

        return self::SUCCESS;
    }
}
