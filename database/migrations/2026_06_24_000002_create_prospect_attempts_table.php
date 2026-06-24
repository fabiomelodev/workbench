<?php

use App\Models\Prospect;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prospect_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Prospect::class)->constrained()->cascadeOnDelete();
            $table->string('channel')->nullable();
            $table->date('attempted_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospect_attempts');
    }
};
