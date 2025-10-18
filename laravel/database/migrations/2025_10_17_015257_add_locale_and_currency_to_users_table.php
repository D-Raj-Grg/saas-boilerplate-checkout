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
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 10)->default('en')->after('metadata'); // en, ne, etc.
            $table->string('currency_preference', 3)->default('NPR')->after('locale'); // NPR, USD, EUR
            $table->string('timezone', 50)->default('Asia/Kathmandu')->after('currency_preference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locale', 'currency_preference', 'timezone']);
        });
    }
};
