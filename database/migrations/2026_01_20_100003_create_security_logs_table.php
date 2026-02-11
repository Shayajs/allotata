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
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event_type'); // 'login', 'logout', 'password_reset', 'suspicious_activity', 'account_locked', etc.
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->string('location')->nullable(); // Pays/Ville si disponible
            $table->json('metadata')->nullable(); // Détails supplémentaires
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->boolean('is_suspicious')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Index pour les recherches
            $table->index('user_id');
            $table->index('event_type');
            $table->index('ip_address');
            $table->index('is_suspicious');
            $table->index('severity');
            $table->index(['user_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
            
            // Clés étrangères
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_logs');
    }
};
