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
            $table->boolean('prestation_libre_active')->default(false)->after('accepter_reservations_auto');
            $table->decimal('tarif_horaire', 8, 2)->nullable()->after('prestation_libre_active');
            $table->string('prestation_libre_description', 255)->nullable()->after('tarif_horaire');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropColumn(['prestation_libre_active', 'tarif_horaire', 'prestation_libre_description']);
        });
    }
};
