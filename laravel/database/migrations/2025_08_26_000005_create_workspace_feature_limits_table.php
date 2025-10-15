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
        Schema::create('workspace_feature_limits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('feature');
            $table->integer('allocated');
            $table->timestamps();

            $table->unique(['workspace_id', 'feature']);
            $table->index(['organization_id', 'feature']);
            $table->index('feature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_feature_limits');
    }
};
