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
        Schema::table('proposition_rendez_vouses', function (Blueprint $table) {
            // Vérifier si duree_minutes existe déjà
            if (!Schema::hasColumn('proposition_rendez_vouses', 'duree_minutes')) {
                $table->integer('duree_minutes')->nullable()->after('heure_fin');
            }
            // Ajouter type_service_id
            if (!Schema::hasColumn('proposition_rendez_vouses', 'type_service_id')) {
                $table->foreignId('type_service_id')->nullable()->after('entreprise_id')->constrained('types_services')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposition_rendez_vouses', function (Blueprint $table) {
            if (Schema::hasColumn('proposition_rendez_vouses', 'type_service_id')) {
                $table->dropForeign(['type_service_id']);
                $table->dropColumn('type_service_id');
            }
            if (Schema::hasColumn('proposition_rendez_vouses', 'duree_minutes')) {
                $table->dropColumn('duree_minutes');
            }
        });
    }
};
