<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Número de telefone "cru" (apenas dígitos) - fonte de verdade.
            $table->string('phone')->nullable()->after('whatsapp');
            // Classificação automática: mobile | landline | other | invalid.
            $table->string('phone_type')->nullable()->index()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['phone', 'phone_type']);
        });
    }
};
