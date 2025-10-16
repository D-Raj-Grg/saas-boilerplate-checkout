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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Relations
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_plan_id')->nullable()->constrained()->onDelete('set null');

            // Payment gateway info
            $table->string('gateway', 50)->index(); // esewa, khalti, fonepay
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NPR');

            // Payment status
            $table->string('status', 50)->default('pending')->index(); // pending, completed, failed, refunded

            // Gateway transaction details
            $table->string('gateway_transaction_id')->nullable()->index();
            $table->json('gateway_response')->nullable();

            // Additional data
            $table->json('metadata')->nullable(); // Store plan_slug, source, etc.
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['organization_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['gateway', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
