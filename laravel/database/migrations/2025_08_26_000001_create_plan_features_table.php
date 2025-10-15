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
        Schema::create('plan_features', function (Blueprint $table): void {
            $table->id();
            $table->string('feature')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['boolean', 'limit'])->default('limit');
            $table->enum('tracking_scope', ['organization', 'workspace'])->default('organization');
            $table->enum('period', ['lifetime', 'monthly', 'weekly', 'daily', 'yearly'])->default('lifetime');
            $table->string('category')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('feature');
            $table->index(['category', 'display_order']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};
