<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('currency', 3)->default('NPR')->after('price');
            $table->json('prices')->nullable()->after('currency'); // For storing multiple currency prices
            $table->string('market', 50)->default('nepal')->after('prices')->index(); // nepal, international
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['currency', 'prices', 'market']);
        });
    }
};
