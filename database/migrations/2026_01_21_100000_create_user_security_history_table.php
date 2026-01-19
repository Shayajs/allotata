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
        Schema::create('user_security_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('type', ['password', 'email']); // Type de changement
            $table->string('old_value_hash')->nullable(); // Hash de l'ancien mot de passe ou email (chiffré pour email)
            $table->string('new_value_hash')->nullable(); // Hash du nouveau mot de passe ou email (chiffré pour email)
            $table->string('changed_by')->nullable(); // ID de l'admin qui a fait le changement (null si auto)
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->text('reason')->nullable(); // Raison du changement (ex: "Réinitialisation par admin")
            $table->timestamps();
            
            // Index pour les recherches
            $table->index('user_id');
            $table->index('type');
            $table->index('created_at');
            $table->index(['user_id', 'type']);
            
            // Clés étrangères
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_security_history');
    }
};
