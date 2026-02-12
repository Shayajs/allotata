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
        Schema::table('messages', function (Blueprint $table) {
            $table->string('type_message')->default('texte')->after('est_lu');
            $table->foreignId('proposition_rdv_id')->nullable()->after('type_message')->constrained('proposition_rendez_vouses')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['proposition_rdv_id']);
            $table->dropColumn(['type_message', 'proposition_rdv_id']);
        });
    }
};
