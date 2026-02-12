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
            // Abonnement manuel (géré par admin)
            $table->boolean('abonnement_manuel')->default(false)->after('afficher_nom_gerant');
            $table->date('abonnement_manuel_actif_jusqu')->nullable()->after('abonnement_manuel');
            $table->text('abonnement_manuel_notes')->nullable()->after('abonnement_manuel_actif_jusqu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropColumn(['abonnement_manuel', 'abonnement_manuel_actif_jusqu', 'abonnement_manuel_notes']);
        });
    }
};
