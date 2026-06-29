<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('prospect_attempts', function (Blueprint $table) {
            // Desfecho da tentativa: no_answer, responded, callback, meeting,
            // not_interested, closed, wrong_number.
            $table->string('outcome')->nullable()->index()->after('channel');
        });
    }

    public function down(): void
    {
        Schema::table('prospect_attempts', function (Blueprint $table) {
            $table->dropColumn('outcome');
        });
    }
};
