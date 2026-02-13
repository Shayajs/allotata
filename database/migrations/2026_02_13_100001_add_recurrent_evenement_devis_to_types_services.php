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
        Schema::table('types_services', function (Blueprint $table) {
            // Champs pour le type "recurrent"
            $table->string('frequence_recurrence')->nullable()->after('type_structure');
            $table->integer('intervalle_jours')->nullable()->after('frequence_recurrence');

            // Champs pour le type "evenement"
            $table->integer('capacite_max')->nullable()->after('intervalle_jours');
            $table->integer('seuil_minimum')->nullable()->after('capacite_max');
            $table->boolean('est_prix_par_personne')->default(true)->after('seuil_minimum');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('types_services', function (Blueprint $table) {
            $table->dropColumn([
                'frequence_recurrence',
                'intervalle_jours',
                'capacite_max',
                'seuil_minimum',
                'est_prix_par_personne',
            ]);
        });
    }
};
