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
        Schema::table('realisation_photos', function (Blueprint $table) {
            $table->foreignId('service_avis_id')->nullable()->after('avis_id')->constrained('service_avis')->onDelete('cascade');
            $table->foreignId('produit_avis_id')->nullable()->after('service_avis_id')->constrained('produit_avis')->onDelete('cascade');
            
            $table->index('service_avis_id');
            $table->index('produit_avis_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('realisation_photos', function (Blueprint $table) {
            $table->dropForeign(['service_avis_id']);
            $table->dropForeign(['produit_avis_id']);
            $table->dropIndex(['service_avis_id']);
            $table->dropIndex(['produit_avis_id']);
            $table->dropColumn(['service_avis_id', 'produit_avis_id']);
        });
    }
};
