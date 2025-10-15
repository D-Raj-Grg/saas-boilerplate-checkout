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
        Schema::create('user_verifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['password_reset', 'two_factor', 'email_verify', 'phone_verify']);
            $table->enum('channel', ['email', 'sms', 'authenticator'])->default('email');
            $table->string('identifier');
            $table->string('code', 10)->nullable();
            $table->string('token', 64)->nullable()->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('verified_at')->nullable();
            $table->integer('attempts')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['identifier', 'code', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_verifications');
    }
};
