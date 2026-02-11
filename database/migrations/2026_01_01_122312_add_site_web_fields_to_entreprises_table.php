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
            // Slug pour le site web vitrine (/w/{slug_web})
            $table->string('slug_web')->unique()->nullable()->after('slug');
            
            // Contenu du site web vitrine (JSON pour stocker les sections configurables)
            $table->json('contenu_site_web')->nullable()->after('slug_web');
            
            // Phrase d'accroche pour le site web
            $table->string('phrase_accroche')->nullable()->after('contenu_site_web');
            
            // Photos pour le site web (stockées séparément des photos de réalisations)
            // On utilisera la table realisation_photos existante ou on peut créer une relation
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropColumn(['slug_web', 'contenu_site_web', 'phrase_accroche', 'site_web_externe']);
        });
    }
};
