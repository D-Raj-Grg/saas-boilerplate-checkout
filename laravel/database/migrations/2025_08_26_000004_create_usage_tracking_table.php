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
        Schema::create('usage_tracking', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('workspace_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('feature');
            $table->integer('current_usage')->default(0);
            $table->enum('period_type', ['lifetime', 'monthly', 'weekly', 'daily', 'yearly']);
            $table->timestamp('period_starts_at')->nullable();
            $table->timestamp('period_ends_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'feature', 'period_ends_at']);
            $table->index(['workspace_id', 'feature', 'period_ends_at']);
            $table->index('period_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_tracking');
    }
};
