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
        Schema::create('essais_gratuits', function (Blueprint $table) {
            $table->id();
            
            // ═══════════════════════════════════════════
            // 🎯 IDENTIFICATION
            // ═══════════════════════════════════════════
            $table->morphs('essayable'); // User ou Entreprise
            $table->string('type_abonnement'); // 'premium', 'site_web', 'multi_personnes'
            
            // ═══════════════════════════════════════════
            // 📅 PÉRIODE D'ESSAI
            // ═══════════════════════════════════════════
            $table->datetime('date_debut');
            $table->datetime('date_fin');
            $table->integer('duree_jours')->default(7); // Durée originale accordée
            
            // ═══════════════════════════════════════════
            // 🔄 STATUT & CYCLE DE VIE
            // ═══════════════════════════════════════════
            $table->enum('statut', [
                'actif',           // En cours
                'expire',          // Terminé sans conversion
                'converti',        // Converti en abonnement payant
                'annule',          // Annulé manuellement (admin ou user)
                'revoque',         // Révoqué par admin (abus, etc.)
            ])->default('actif');
            
            $table->datetime('date_conversion')->nullable(); // Quand il a souscrit
            $table->datetime('date_annulation')->nullable(); // Quand annulé/révoqué
            $table->string('raison_annulation')->nullable(); // Pourquoi annulé
            
            // ═══════════════════════════════════════════
            // 📧 NOTIFICATIONS
            // ═══════════════════════════════════════════
            $table->datetime('notification_rappel_envoye_le')->nullable(); // J-2 avant fin
            $table->datetime('notification_expiration_envoye_le')->nullable(); // Le jour de fin
            $table->datetime('notification_relance_envoye_le')->nullable(); // J+3 après fin
            
            // ═══════════════════════════════════════════
            // 📈 STATISTIQUES D'ENGAGEMENT (pendant l'essai)
            // ═══════════════════════════════════════════
            $table->integer('nb_connexions')->default(0); // Combien de fois connecté
            $table->datetime('derniere_connexion')->nullable();
            $table->integer('nb_actions')->default(0); // Actions clés effectuées
            
            // Spécifique au type (JSON pour flexibilité)
            $table->json('metriques')->nullable();
            
            // ═══════════════════════════════════════════
            // 🎁 SOURCE & MARKETING
            // ═══════════════════════════════════════════
            $table->string('source')->nullable(); // Comment a-t-il eu l'essai ?
            $table->string('code_promo_utilise')->nullable(); // Si via code promo
            $table->foreignId('parrain_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('utm_source')->nullable(); // Tracking marketing
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            
            // ═══════════════════════════════════════════
            // 🛡️ CONTRÔLE & SÉCURITÉ
            // ═══════════════════════════════════════════
            $table->string('ip_activation')->nullable(); // IP lors de l'activation
            $table->string('user_agent')->nullable(); // Browser info
            $table->foreignId('accorde_par_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes_admin')->nullable(); // Notes internes
            
            // ═══════════════════════════════════════════
            // 💰 VALEUR & CONVERSION
            // ═══════════════════════════════════════════
            $table->decimal('valeur_essai', 8, 2)->nullable(); // Valeur estimée de l'essai
            $table->unsignedBigInteger('abonnement_converti_id')->nullable(); // Lien vers l'abonnement créé
            $table->string('abonnement_converti_type')->nullable(); // Type polymorphique
            
            // ═══════════════════════════════════════════
            // 📝 FEEDBACK
            // ═══════════════════════════════════════════
            $table->tinyInteger('note_satisfaction')->nullable(); // 1-5 étoiles
            $table->text('feedback')->nullable(); // Commentaire libre
            $table->string('raison_non_conversion')->nullable(); // Si expiré sans convertir
            
            $table->timestamps();
            
            // ═══════════════════════════════════════════
            // 🔍 INDEX POUR PERFORMANCE
            // ═══════════════════════════════════════════
            $table->index(['essayable_type', 'essayable_id', 'type_abonnement'], 'essais_essayable_type_idx');
            $table->index(['statut', 'date_fin'], 'essais_statut_date_fin_idx');
            $table->index('source');
            $table->index('date_debut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('essais_gratuits');
    }
};
