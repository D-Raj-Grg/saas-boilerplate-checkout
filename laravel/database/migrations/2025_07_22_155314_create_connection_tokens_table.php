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
        Schema::create('connection_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->string('redirect_url');
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamps();

            $table->index(['token', 'used']);
            $table->index(['user_id', 'workspace_id']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connection_tokens');
    }
};
