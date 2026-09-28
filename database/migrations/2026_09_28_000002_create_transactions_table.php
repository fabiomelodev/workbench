<?php

use App\Models\Proposal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Proposal::class)->constrained()->cascadeOnDelete();
            // Orçamento fechado: valor total negociado. Assinatura: valor da mensalidade.
            $table->decimal('amount', 10, 2);
            // Nº de parcelas do orçamento fechado; null = assinatura (parcelas mensais sem fim).
            $table->unsignedTinyInteger('installments')->nullable();
            $table->string('status')->default('pending')->index();
            // Quitação: preenchido automaticamente quando todas as parcelas são pagas.
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
