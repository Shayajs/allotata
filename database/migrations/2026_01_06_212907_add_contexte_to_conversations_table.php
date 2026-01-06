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
        Schema::table('conversations', function (Blueprint $table) {
            // Ajouter les colonnes pour le contexte produit et service
            $table->foreignId('produit_id')->nullable()->after('reservation_id')->constrained('produits')->onDelete('set null');
            $table->foreignId('type_service_id')->nullable()->after('produit_id')->constrained('types_services')->onDelete('set null');
            
            // Index pour améliorer les performances
            $table->index('produit_id');
            $table->index('type_service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['produit_id']);
            $table->dropForeign(['type_service_id']);
            $table->dropIndex(['produit_id']);
            $table->dropIndex(['type_service_id']);
            $table->dropColumn(['produit_id', 'type_service_id']);
        });
    }
};
