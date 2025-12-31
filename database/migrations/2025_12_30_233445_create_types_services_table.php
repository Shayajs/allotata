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
        Schema::create('types_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained()->onDelete('cascade');
            
            $table->string('nom'); // Ex: "Tressage simple", "Repas complet", etc.
            $table->text('description')->nullable();
            $table->integer('duree_minutes'); // Durée en minutes
            $table->decimal('prix', 10, 2); // Prix du service
            $table->boolean('est_actif')->default(true); // Pour activer/désactiver un service
            
            $table->timestamps();
            
            $table->index('entreprise_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('types_services');
    }
};
