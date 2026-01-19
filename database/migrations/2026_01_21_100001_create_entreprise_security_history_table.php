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
        Schema::create('entreprise_security_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entreprise_id');
            $table->enum('type', ['email']); // Pour l'instant seulement email (les entreprises n'ont pas de mot de passe)
            $table->string('old_value_hash')->nullable(); // Email chiffré
            $table->string('new_value_hash')->nullable(); // Email chiffré
            $table->string('changed_by')->nullable(); // ID de l'admin qui a fait le changement (null si auto)
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->text('reason')->nullable(); // Raison du changement
            $table->timestamps();
            
            // Index pour les recherches
            $table->index('entreprise_id');
            $table->index('type');
            $table->index('created_at');
            $table->index(['entreprise_id', 'type']);
            
            // Clés étrangères
            $table->foreign('entreprise_id')->references('id')->on('entreprises')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entreprise_security_history');
    }
};
