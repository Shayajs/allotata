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
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('membre_id')->nullable()->after('entreprise_id')->constrained('entreprise_membres')->onDelete('set null');
            $table->index('membre_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['membre_id']);
            $table->dropIndex(['membre_id']);
            $table->dropColumn('membre_id');
        });
    }
};
