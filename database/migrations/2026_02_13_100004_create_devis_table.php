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
        Schema::create('devis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entreprise_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('type_service_id')->constrained('types_services')->onDelete('cascade');
            $table->text('description_besoin'); // Ce que le client demande
            $table->string('statut')->default('en_attente'); // en_attente, propose, accepte, refuse
            // Proposition du prestataire
            $table->decimal('montant_propose', 10, 2)->nullable();
            $table->string('type_structure_propose')->nullable(); // ponctuel, multi_rendez_vous, etc.
            $table->dateTime('date_proposee')->nullable();
            $table->integer('duree_proposee_minutes')->nullable();
            $table->text('notes_prestataire')->nullable();
            // Réservation générée après acceptation
            $table->foreignId('reservation_id')->nullable()->constrained()->onDelete('set null');
            // Client non inscrit
            $table->string('nom_client')->nullable();
            $table->string('email_client')->nullable();
            $table->string('telephone_client')->nullable();
            $table->timestamps();

            $table->index('entreprise_id');
            $table->index('user_id');
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devis');
    }
};
