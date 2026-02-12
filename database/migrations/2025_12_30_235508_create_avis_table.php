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
        Schema::create('avis', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Le client qui a laissé l'avis
            $table->foreignId('entreprise_id')->constrained()->onDelete('cascade'); // L'entreprise notée
            $table->foreignId('reservation_id')->nullable()->constrained()->onDelete('set null'); // La réservation liée (optionnel)
            
            // Note et commentaire
            $table->integer('note')->comment('Note de 1 à 5'); // 1 à 5 étoiles
            $table->text('commentaire')->nullable(); // Commentaire optionnel
            
            // Statut (pour modération si nécessaire)
            $table->boolean('est_approuve')->default(true); // Par défaut approuvé
            
            $table->timestamps();
            
            // Un utilisateur ne peut laisser qu'un seul avis par entreprise
            $table->unique(['user_id', 'entreprise_id']);
            $table->index('entreprise_id');
            $table->index('note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avis');
    }
};
