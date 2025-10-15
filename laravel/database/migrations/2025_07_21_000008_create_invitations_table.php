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
        Schema::create('invitations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('workspace_assignments')->nullable();
            $table->string('email')->index();
            $table->string('role', 50)->default('member');
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired', 'cancelled'])->default('pending');
            $table->text('message')->nullable();
            $table->foreignId('inviter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token')->unique();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expired_at_actual')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->index('workspace_id');
            $table->index('organization_id');
            $table->index(['organization_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
