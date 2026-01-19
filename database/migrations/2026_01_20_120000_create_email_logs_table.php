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
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('recipient_email');
            $table->string('subject');
            $table->string('type')->nullable(); // 'verification', 'welcome', 'password_reset', etc.
            $table->enum('status', ['sent', 'failed', 'pending'])->default('pending');
            $table->text('error_message')->nullable();
            $table->text('content_preview')->nullable(); // Aperçu du contenu
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            
            // Index pour les recherches
            $table->index('user_id');
            $table->index('recipient_email');
            $table->index('type');
            $table->index('status');
            $table->index('created_at');
            $table->index(['recipient_email', 'created_at']);
            
            // Clés étrangères
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
