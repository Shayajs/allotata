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
            if (!Schema::hasColumn('proposition_rendez_vouses', 'duree_minutes')) {
                $table->integer('duree_minutes')->default(30)->after('heure_fin');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proposition_rendez_vouses', function (Blueprint $table) {
            if (Schema::hasColumn('proposition_rendez_vouses', 'duree_minutes')) {
                $table->dropColumn('duree_minutes');
            }
        });
    }
};
