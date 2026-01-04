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
        Schema::table('entreprises', function (Blueprint $table) {
            // Paramètres fiscaux pour le calcul de l'impôt sur le revenu
            $table->string('fiscal_situation_familiale')->nullable()->default('celibataire'); // celibataire, marie, pacse, veuf, divorce
            $table->integer('fiscal_nombre_enfants')->nullable()->default(0);
            $table->integer('fiscal_enfants_garde_alternee')->nullable()->default(0); // Nombre d'enfants en garde alternée (quarts de parts)
            $table->boolean('fiscal_parent_isole')->nullable()->default(false); // Parent isolé = +0.5 part
            $table->boolean('fiscal_prelevement_liberatoire')->nullable()->default(false); // Option pour le versement libératoire
            $table->decimal('fiscal_revenu_fiscal_reference', 12, 2)->nullable(); // RFR N-2 pour éligibilité PL
            $table->decimal('fiscal_revenus_autres_foyer', 12, 2)->nullable()->default(0); // Autres revenus du foyer (salaires conjoint, etc.)
            $table->boolean('fiscal_invalidite_contribuable')->nullable()->default(false); // +0.5 part si carte invalidité
            $table->boolean('fiscal_invalidite_conjoint')->nullable()->default(false); // +0.5 part si carte invalidité conjoint
            $table->boolean('fiscal_ancien_combattant')->nullable()->default(false); // +0.5 part si >74 ans et carte combattant
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropColumn([
                'fiscal_situation_familiale',
                'fiscal_nombre_enfants',
                'fiscal_enfants_garde_alternee',
                'fiscal_parent_isole',
                'fiscal_prelevement_liberatoire',
                'fiscal_revenu_fiscal_reference',
                'fiscal_revenus_autres_foyer',
                'fiscal_invalidite_contribuable',
                'fiscal_invalidite_conjoint',
                'fiscal_ancien_combattant',
            ]);
        });
    }
};
