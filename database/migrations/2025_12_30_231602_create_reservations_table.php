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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Le client
            $table->foreignId('entreprise_id')->constrained()->onDelete('cascade'); // L'entreprise
            
            // Informations de la réservation
            $table->dateTime('date_reservation'); // Date et heure du rendez-vous
            $table->string('lieu')->nullable(); // Adresse ou lieu du rendez-vous
            $table->text('notes')->nullable(); // Notes supplémentaires du client
            
            // Informations financières
            $table->decimal('prix', 10, 2); // Prix de la réservation
            $table->boolean('est_paye')->default(false); // Statut de paiement
            $table->timestamp('date_paiement')->nullable(); // Date du paiement si payé
            
            // Statut de la réservation
            $table->enum('statut', ['en_attente', 'confirmee', 'annulee', 'terminee'])->default('en_attente');
            
            // Informations de service
            $table->string('type_service')->nullable(); // Type de service (tressage, repas, etc.)
            $table->integer('duree_minutes')->nullable(); // Durée estimée en minutes
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
