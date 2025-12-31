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
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('reservation_id')->constrained()->onDelete('cascade');
            $table->foreignId('entreprise_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Le client
            
            // Informations de la facture
            $table->string('numero_facture')->unique(); // Numéro unique de facture
            $table->date('date_facture'); // Date d'émission
            $table->date('date_echeance')->nullable(); // Date d'échéance
            
            // Informations financières
            $table->decimal('montant_ht', 10, 2); // Montant HT
            $table->decimal('taux_tva', 5, 2)->default(0); // Taux de TVA (0% par défaut)
            $table->decimal('montant_tva', 10, 2)->default(0); // Montant de la TVA
            $table->decimal('montant_ttc', 10, 2); // Montant TTC
            
            // Statut
            $table->enum('statut', ['brouillon', 'emise', 'payee', 'annulee'])->default('emise');
            
            // Informations supplémentaires
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index('numero_facture');
            $table->index('date_facture');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
