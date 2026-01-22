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
            // Type de structure du service :
            // - 'ponctuel' : service qui prend X minutes dans une journée (comportement actuel)
            // - 'multi_jours' : service qui s'étend sur plusieurs jours (ex: photographie)
            // - 'multi_rendez_vous' : service avec plusieurs rendez-vous liés (ex: création de site web)
            $table->string('type_structure')->default('ponctuel')->after('duree_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('types_services', function (Blueprint $table) {
            $table->dropColumn('type_structure');
        });
    }
};
