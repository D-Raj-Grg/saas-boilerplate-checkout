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
        Schema::create('waitlists', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->string('status')->default('pending'); // pending, contacted, converted
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('email');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waitlists');
    }
};
