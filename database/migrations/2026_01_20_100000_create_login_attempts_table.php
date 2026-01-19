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
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email', 255);
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->boolean('success')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('failure_reason')->nullable(); // 'invalid_credentials', 'account_locked', etc.
            $table->timestamp('attempted_at');
            $table->timestamps();
            
            // Index pour les recherches
            $table->index('email');
            $table->index('ip_address');
            $table->index('user_id');
            $table->index(['email', 'attempted_at']);
            $table->index(['ip_address', 'attempted_at']);
            $table->index(['user_id', 'attempted_at']);
            
            // Clés étrangères
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
