<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->unsignedSmallInteger('intervalle_creneaux_minutes')
                ->default(30)
                ->after('accepter_reservations_auto');
        });
    }

    public function down(): void
    {
        Schema::table('entreprises', function (Blueprint $table) {
            $table->dropColumn('intervalle_creneaux_minutes');
        });
    }
};
