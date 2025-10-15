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
        Schema::create('organization_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('purchase_uuid')->nullable();
            $table->longText('checkout_uuid')->nullable();
            $table->string('sc_price_uuid')->nullable();
            $table->timestamp('trial_start')->nullable();
            $table->timestamp('trial_end')->nullable();
            $table->foreignId('plan_id'); // No foreign key constraint
            $table->string('status', 50)->default('active'); // active, inactive, cancelled, expired
            $table->boolean('is_revoked')->default(false);
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('started_at')->default(now());
            $table->timestamp('ends_at')->nullable();
            $table->string('billing_cycle')->default('monthly'); // monthly, yearly, lifetime
            $table->decimal('charging_price', 10, 2)->nullable();
            $table->string('charging_currency', 3)->nullable();
            $table->integer('quantity')->default(1);
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'is_revoked']);
            $table->index(['status', 'ends_at']);
            $table->index('purchase_uuid');
            $table->index('sc_price_uuid');
            $table->index('checkout_uuid');
            $table->index(['organization_id', 'status', 'is_revoked']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_plans');
    }
};
