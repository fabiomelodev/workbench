<?php

use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Parcelas de uma cobrança: previstas na criação, pagas conforme o cliente paga.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Transaction::class)->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('number');
            $table->decimal('amount', 10, 2);
            $table->date('due_date')->index();
            // null = em aberto.
            $table->date('paid_at')->nullable()->index();
            $table->string('method')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['transaction_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
