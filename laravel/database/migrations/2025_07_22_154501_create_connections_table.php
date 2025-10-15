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
        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('integration_name')->default('wordpress');
            $table->string('site_url')->nullable();
            $table->text('config')->nullable(); // Encrypted JSON data
            $table->enum('status', ['active', 'inactive', 'error'])->default('active');
            $table->string('plugin_version')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'integration_name']);
            $table->index(['user_id', 'integration_name']);
            $table->index('uuid');
            $table->unique(['workspace_id', 'site_url', 'integration_name'], 'connections_workspace_site_integration_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connections');
    }
};
