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
        Schema::create('horaires_ouverture', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained()->onDelete('cascade');
            
            // Jour de la semaine (0 = dimanche, 1 = lundi, ..., 6 = samedi)
            $table->integer('jour_semaine')->comment('0=dimanche, 1=lundi, ..., 6=samedi');
            
            // Horaires
            $table->time('heure_ouverture')->nullable(); // null = fermé ce jour
            $table->time('heure_fermeture')->nullable();
            
            // Indique si c'est un horaire exceptionnel (pour les jours spécifiques)
            $table->boolean('est_exceptionnel')->default(false);
            $table->date('date_exception')->nullable(); // Pour les jours spécifiques
            
            $table->timestamps();
            
            // Index pour les recherches
            $table->index(['entreprise_id', 'jour_semaine']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horaires_ouverture');
    }
};
