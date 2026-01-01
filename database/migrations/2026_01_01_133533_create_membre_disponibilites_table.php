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
        Schema::create('membre_disponibilites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membre_id')->constrained('entreprise_membres')->onDelete('cascade');
            $table->integer('jour_semaine')->comment('0=Dimanche, 1=Lundi, ..., 6=Samedi');
            $table->time('heure_debut')->nullable();
            $table->time('heure_fin')->nullable();
            $table->boolean('est_exceptionnel')->default(false);
            $table->date('date_exception')->nullable();
            $table->boolean('est_disponible')->default(true);
            $table->text('raison_indisponibilite')->nullable();
            $table->timestamps();
            
            $table->index(['membre_id', 'jour_semaine']);
            $table->index(['membre_id', 'est_exceptionnel', 'date_exception']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membre_disponibilites');
    }
};
