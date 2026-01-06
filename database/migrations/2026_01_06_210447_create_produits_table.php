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
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained()->onDelete('cascade');
            
            $table->string('nom'); // Nom du produit
            $table->text('description')->nullable(); // Description du produit
            $table->decimal('prix', 10, 2); // Prix du produit
            $table->boolean('est_actif')->default(true); // Produit actif/inactif
            // Type de gestion: 'disponible_immediatement' ou 'en_attente_commandes'
            $table->enum('gestion_stock', ['disponible_immediatement', 'en_attente_commandes'])->default('disponible_immediatement');
            
            $table->timestamps();
            
            $table->index('entreprise_id');
            $table->index('est_actif');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
