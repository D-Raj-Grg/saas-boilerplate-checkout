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
        Schema::create('plan_limits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->string('feature');
            $table->string('type');
            $table->enum('tracking_scope', ['organization', 'workspace'])->default('organization');
            $table->string('value');
            $table->timestamps();

            $table->unique(['plan_id', 'feature']);
            $table->index('feature');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_limits');
    }
};
