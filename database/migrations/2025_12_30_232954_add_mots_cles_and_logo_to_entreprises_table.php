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
            $table->text('mots_cles')->nullable()->after('description'); // Mots-clés séparés par des virgules
            $table->string('logo')->nullable()->after('mots_cles'); // Chemin vers le logo/image
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropColumn(['mots_cles', 'logo']);
        });
    }
};
