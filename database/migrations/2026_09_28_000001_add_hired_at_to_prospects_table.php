<?php

use App\Models\Prospect;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            // Quando a prospecção passou para "Contratado" (preenchido pelo model).
            $table->timestamp('hired_at')->nullable()->index()->after('next_action');
        });

        // Backfill: para as já contratadas, a melhor aproximação é a última alteração.
        DB::table('prospects')
            ->where('status', Prospect::HIRED)
            ->update(['hired_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('prospects', function (Blueprint $table) {
            $table->dropColumn('hired_at');
        });
    }
};
