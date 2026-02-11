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
            $table->foreignId('avis_id')->nullable()->after('entreprise_id')->constrained('avis')->onDelete('cascade');
            $table->index('avis_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('realisation_photos', function (Blueprint $table) {
            $table->dropForeign(['avis_id']);
            $table->dropIndex(['avis_id']);
            $table->dropColumn('avis_id');
        });
    }
};
