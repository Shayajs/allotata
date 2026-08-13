<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->string('siret', 14)->nullable()->after('siren');
            $table->string('tva_intracommunautaire', 20)->nullable()->after('siret');
            $table->boolean('assujetti_tva')->default(false)->after('tva_intracommunautaire');
            $table->decimal('taux_tva_defaut', 5, 2)->default(20)->after('assujetti_tva');
            $table->decimal('capital_social', 12, 2)->nullable()->after('taux_tva_defaut');
            $table->string('rcs_ville')->nullable()->after('capital_social');
            $table->string('nom_responsable')->nullable()->after('rcs_ville');
            $table->string('pdf_couleur_primaire', 7)->default('#059669')->after('nom_responsable');
            $table->string('pdf_couleur_secondaire', 7)->default('#1f2937')->after('pdf_couleur_primaire');
        });
    }

    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropColumn([
                'siret',
                'tva_intracommunautaire',
                'assujetti_tva',
                'taux_tva_defaut',
                'capital_social',
                'rcs_ville',
                'nom_responsable',
                'pdf_couleur_primaire',
                'pdf_couleur_secondaire',
            ]);
        });
    }
};
