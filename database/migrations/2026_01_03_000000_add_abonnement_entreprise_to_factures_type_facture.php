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
        Schema::table('factures', function (Blueprint $table) {
            // Modifier l'enum pour ajouter 'abonnement_entreprise'
            \DB::statement("ALTER TABLE factures MODIFY COLUMN type_facture ENUM('reservation', 'abonnement_manuel', 'abonnement_entreprise') DEFAULT 'reservation'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            // Revenir à l'enum précédent
            \DB::statement("ALTER TABLE factures MODIFY COLUMN type_facture ENUM('reservation', 'abonnement_manuel') DEFAULT 'reservation'");
        });
    }
};
