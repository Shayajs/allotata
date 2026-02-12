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
        Schema::table('brightshell_devis', function (Blueprint $table) {
            $table->date('date_devis')->nullable()->after('client_id');
        });
        
        // Rétro-compatibilité : remplir date_devis avec created_at pour les anciens devis
        \DB::table('brightshell_devis')
            ->whereNull('date_devis')
            ->update(['date_devis' => \DB::raw('DATE(created_at)')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brightshell_devis', function (Blueprint $table) {
            $table->dropColumn('date_devis');
        });
    }
};
