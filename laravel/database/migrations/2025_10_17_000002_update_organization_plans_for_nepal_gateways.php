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
        Schema::table('organization_plans', function (Blueprint $table) {
            // Remove SureCart-specific columns
            $table->dropColumn([
                'purchase_uuid',
                'checkout_uuid',
                'sc_price_uuid',
            ]);

            // Add Nepal payment gateway columns
            $table->string('payment_gateway', 50)->nullable()->after('plan_id'); // esewa, khalti, fonepay, free
            $table->string('gateway_transaction_id')->nullable()->after('payment_gateway')->index();
            $table->string('gateway_customer_id')->nullable()->after('gateway_transaction_id');

            // Update indexes
            $table->index(['organization_id', 'payment_gateway']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_plans', function (Blueprint $table) {
            // Drop Nepal gateway columns
            $table->dropIndex(['organization_id', 'payment_gateway']);
            $table->dropColumn([
                'payment_gateway',
                'gateway_transaction_id',
                'gateway_customer_id',
            ]);

            // Restore SureCart columns
            $table->string('purchase_uuid')->nullable();
            $table->longText('checkout_uuid')->nullable();
            $table->string('sc_price_uuid')->nullable();

            $table->index('purchase_uuid');
            $table->index('sc_price_uuid');
            $table->index('checkout_uuid');
        });
    }
};
