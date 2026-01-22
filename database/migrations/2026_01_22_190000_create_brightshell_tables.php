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
        // Table des clients BrightShell
        Schema::create('brightshell_clients', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom')->nullable();
            $table->string('societe')->nullable();
            $table->string('siret', 14)->nullable();
            $table->string('tva_intracommunautaire', 20)->nullable();
            $table->text('adresse')->nullable();
            $table->string('code_postal', 10)->nullable();
            $table->string('ville')->nullable();
            $table->string('pays')->default('France');
            $table->string('email')->nullable();
            $table->string('telephone', 20)->nullable();
            $table->enum('statut', ['prospect', 'actif', 'litige', 'archive'])->default('prospect');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('statut');
            $table->index('email');
        });

        // Table des devis BrightShell
        Schema::create('brightshell_devis', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique(); // D-2026-001
            $table->foreignId('client_id')->constrained('brightshell_clients')->onDelete('cascade');
            $table->string('objet');
            $table->json('lignes'); // [{description, quantite, prix_unitaire}]
            $table->decimal('montant_ht', 10, 2);
            $table->text('notes')->nullable();
            $table->text('conditions')->nullable();
            $table->integer('validite_jours')->default(30);
            $table->date('date_validite')->nullable();
            $table->enum('statut', ['brouillon', 'envoye', 'accepte', 'refuse', 'expire'])->default('brouillon');
            $table->timestamp('date_envoi')->nullable();
            $table->timestamp('date_reponse')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('statut');
            $table->index('numero');
        });

        // Table des factures BrightShell
        Schema::create('brightshell_factures', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique(); // F-2026-001
            $table->foreignId('client_id')->constrained('brightshell_clients')->onDelete('cascade');
            $table->foreignId('devis_id')->nullable()->constrained('brightshell_devis')->onDelete('set null');
            $table->string('objet');
            $table->json('lignes'); // [{description, quantite, prix_unitaire}]
            $table->decimal('montant_total', 10, 2);
            $table->text('notes')->nullable();
            $table->integer('echeance_jours')->default(30);
            $table->date('date_echeance')->nullable();
            $table->boolean('est_payee')->default(false);
            $table->timestamp('date_paiement')->nullable();
            $table->string('mode_paiement')->nullable(); // virement, cheque, especes, cb
            $table->text('mention_legale')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('est_payee');
            $table->index('numero');
        });

        // Table des déclarations URSSAF
        Schema::create('brightshell_declarations', function (Blueprint $table) {
            $table->id();
            $table->string('periode'); // "Janvier 2026" ou "T1 2026"
            $table->enum('type_periode', ['mensuel', 'trimestriel'])->default('mensuel');
            $table->decimal('ca_declare', 10, 2);
            $table->decimal('taux_cotisations', 5, 3)->default(0.212); // 21.2%
            $table->decimal('cotisations', 10, 2);
            $table->date('date_declaration');
            $table->boolean('est_payee')->default(false);
            $table->timestamp('date_paiement')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('periode');
        });

        // Table du livre des recettes (obligatoire micro-entreprise)
        Schema::create('brightshell_recettes', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('reference'); // Numéro de facture
            $table->foreignId('client_id')->nullable()->constrained('brightshell_clients')->onDelete('set null');
            $table->string('client_nom'); // Stocké en dur pour l'archivage
            $table->string('nature'); // Nature de la prestation
            $table->decimal('montant', 10, 2);
            $table->string('mode_reglement'); // virement, cheque, especes, cb
            $table->foreignId('facture_id')->nullable()->constrained('brightshell_factures')->onDelete('set null');
            $table->timestamps();
            
            $table->index('date');
        });

        // Table du registre des achats
        Schema::create('brightshell_achats', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('fournisseur');
            $table->string('description');
            $table->string('categorie')->nullable(); // hebergement, logiciel, materiel, etc.
            $table->decimal('montant', 10, 2);
            $table->string('justificatif')->nullable(); // Chemin vers le fichier
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('date');
            $table->index('categorie');
        });

        // Table des projets (time tracking)
        Schema::create('brightshell_projets', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->foreignId('client_id')->nullable()->constrained('brightshell_clients')->onDelete('set null');
            $table->text('description')->nullable();
            $table->enum('statut', ['en_cours', 'en_pause', 'termine', 'annule'])->default('en_cours');
            $table->decimal('tarif_horaire', 8, 2)->nullable();
            $table->decimal('budget_heures', 8, 2)->nullable();
            $table->decimal('heures_passees', 8, 2)->default(0);
            $table->date('date_debut')->nullable();
            $table->date('date_fin_prevue')->nullable();
            $table->date('date_fin_reelle')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('statut');
        });

        // Table des logs de mails (throttle 120/h)
        Schema::create('brightshell_mail_logs', function (Blueprint $table) {
            $table->id();
            $table->string('to');
            $table->string('subject');
            $table->text('body')->nullable();
            $table->enum('status', ['sent', 'failed', 'queued'])->default('sent');
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index('created_at');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brightshell_mail_logs');
        Schema::dropIfExists('brightshell_projets');
        Schema::dropIfExists('brightshell_achats');
        Schema::dropIfExists('brightshell_recettes');
        Schema::dropIfExists('brightshell_declarations');
        Schema::dropIfExists('brightshell_factures');
        Schema::dropIfExists('brightshell_devis');
        Schema::dropIfExists('brightshell_clients');
    }
};
