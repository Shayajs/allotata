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
        Schema::create('commandes_produits', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Le client (peut être null pour non inscrits)
            $table->foreignId('entreprise_id')->constrained()->onDelete('cascade'); // L'entreprise
            $table->foreignId('produit_id')->constrained()->onDelete('cascade'); // Le produit commandé
            $table->foreignId('membre_id')->nullable()->constrained('entreprise_membres')->onDelete('set null'); // Membre assigné
            
            // Informations du client (pour non inscrits)
            $table->string('nom_client')->nullable();
            $table->string('email_client')->nullable();
            $table->string('telephone_client_non_inscrit')->nullable();
            $table->string('telephone_client')->nullable();
            $table->boolean('telephone_cache')->default(false);
            
            // Informations de la commande
            $table->integer('quantite')->default(1); // Quantité commandée
            $table->decimal('prix_unitaire', 10, 2); // Prix unitaire au moment de la commande
            $table->decimal('prix_total', 10, 2); // Prix total (quantite * prix_unitaire)
            $table->text('notes')->nullable(); // Notes supplémentaires du client
            
            // Options de livraison/vente
            $table->enum('mode_livraison', ['livraison', 'vente_sur_place', 'a_discuter'])->default('a_discuter');
            $table->string('adresse_livraison')->nullable(); // Adresse complète de livraison
            $table->string('code_postal_livraison')->nullable();
            $table->string('ville_livraison')->nullable();
            
            // Informations financières
            $table->boolean('est_paye')->default(false); // Statut de paiement
            $table->timestamp('date_paiement')->nullable(); // Date du paiement si payé
            
            // Statut de la commande
            $table->enum('statut', ['en_attente', 'confirmee', 'annulee', 'terminee', 'livree'])->default('en_attente');
            
            // Dates importantes
            $table->dateTime('date_commande')->default(now()); // Date de la commande
            $table->dateTime('date_livraison_souhaitee')->nullable(); // Date souhaitée par le client
            $table->dateTime('date_livraison_prevue')->nullable(); // Date prévue par l'entreprise
            $table->dateTime('date_livraison_reelle')->nullable(); // Date réelle de livraison
            
            // Pour les commandes créées manuellement par l'entreprise
            $table->boolean('creee_manuellement')->default(false);
            
            // Hash pour accès public (comme pour les réservations)
            $table->string('hash', 100)->unique()->nullable();
            
            $table->timestamps();
            
            // Index pour les recherches
            $table->index(['entreprise_id', 'statut']);
            $table->index(['produit_id', 'statut']);
            $table->index(['user_id', 'statut']);
            $table->index('date_commande');
            $table->index('hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commandes_produits');
    }
};
