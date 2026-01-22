<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Solde trésorerie (monnaie) — une seule ligne mise à jour
        Schema::create('brightshell_tresorerie', function (Blueprint $table) {
            $table->id();
            $table->decimal('solde_courant', 12, 2)->default(0);
            $table->timestamp('date_maj')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        DB::table('brightshell_tresorerie')->insert([
            'solde_courant' => 0,
            'date_maj' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Réserves : à garder de côté (URSSAF, impôts, etc.)
        Schema::create('brightshell_reserves', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->decimal('montant', 10, 2);
            $table->date('date_prevue')->nullable();
            $table->boolean('payee')->default(false);
            $table->timestamp('date_paiement')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('date_prevue');
            $table->index('payee');
        });

        // Abonnements : entrées (clients, facilités de paiement) ou sorties (hebdo, SaaS, etc.)
        Schema::create('brightshell_abonnements', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['entree', 'sortie']);
            $table->string('libelle');
            $table->string('beneficiaire')->nullable(); // qui on paye / qui nous paye
            $table->decimal('montant', 10, 2);
            $table->enum('frequence', ['mensuel', 'semaines_strictes'])->default('mensuel');
            $table->unsignedTinyInteger('intervalle_semaines')->nullable(); // ex. 4 = toutes les 4 semaines
            $table->date('date_debut');
            $table->date('date_fin')->nullable(); // fin abo ou facilité de paiement
            $table->date('prochaine_echeance')->nullable();
            $table->boolean('actif')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('type');
            $table->index('actif');
            $table->index('prochaine_echeance');
        });

        // Mouvements manuels : entrées/sorties hors factures & achats
        Schema::create('brightshell_mouvements', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['entree', 'sortie']);
            $table->string('libelle');
            $table->decimal('montant', 10, 2);
            $table->date('date');
            $table->string('categorie')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('type');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brightshell_mouvements');
        Schema::dropIfExists('brightshell_abonnements');
        Schema::dropIfExists('brightshell_reserves');
        Schema::dropIfExists('brightshell_tresorerie');
    }
};
