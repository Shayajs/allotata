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
        Schema::create('visite_clics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visite_id')->constrained('entreprise_visites')->onDelete('cascade');
            $table->enum('type', ['service', 'produit']);
            $table->unsignedBigInteger('item_id'); // ID du service ou produit
            $table->string('item_nom'); // Nom du service/produit (pour historique)
            $table->timestamp('clicked_at');
            $table->timestamps();
            
            // Index pour les recherches
            $table->index(['visite_id', 'type']);
            $table->index(['type', 'item_id']);
            $table->index('clicked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visite_clics');
    }
};
